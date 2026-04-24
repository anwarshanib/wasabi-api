<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientTransaction;
use App\Models\TenantResource;
use App\Models\WebhookEvent;
use App\Services\ClientBalanceService;
use App\Services\FeeService;
use App\Services\TenantOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives and stores inbound Wasabi Card webhook events.
 *
 * Flow:
 *   Wasabi Card API  →  POST /api/webhook  →  This controller
 *
 * This endpoint is intentionally outside the api.auth middleware group —
 * Wasabi does not send our X-API-KEY. Authentication is performed instead
 * by verifying the X-WSB-SIGNATURE RSA signature against the Wasabi
 * platform public key (SHA256withRSA).
 *
 * Wasabi retries on failure 7 times: 1m, 5m, 20m, 1h, 12h, 24h.
 * This controller ALWAYS returns the required acknowledgement structure.
 */
final class WebhookController extends Controller
{
    public function __construct(
        private readonly TenantOwnershipService $ownership,
        private readonly FeeService $feeService,
        private readonly ClientBalanceService $clientBalance,
    ) {}
    /**
     * Receive a Wasabi webhook event.
     *
     * Steps:
     *  1. Read raw body and headers
     *  2. Verify RSA signature (log warning on failure, still store)
     *  3. Idempotency check via X-WSB-REQUEST-ID
     *  4. Extract indexed fields and persist to webhook_events
     *  5. Return Wasabi's required acknowledgement
     */
    public function receive(Request $request): JsonResponse
    {
        $rawBody   = $request->getContent();
        $category  = (string) $request->header('X-WSB-CATEGORY', '');
        $signature = (string) $request->header('X-WSB-SIGNATURE', '');
        $requestId = $request->header('X-WSB-REQUEST-ID');

        // Verify Wasabi's RSA signature on the raw body
        $verified = $this->verifySignature($rawBody, $signature);

        if (! $verified) {
            Log::channel('wasabi')->warning('Webhook: signature verification failed', [
                'category'   => $category,
                'request_id' => $requestId,
            ]);
        }

        // Parse payload (tolerate malformed JSON gracefully)
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            Log::channel('wasabi')->error('Webhook: invalid JSON body', [
                'category'   => $category,
                'request_id' => $requestId,
                'raw'        => mb_substr($rawBody, 0, 500),
            ]);

            return $this->wasabiAck();
        }

        // Idempotency: skip duplicate deliveries from Wasabi's retry mechanism
        if ($requestId && WebhookEvent::where('request_id', $requestId)->exists()) {
            Log::channel('wasabi')->info('Webhook: duplicate delivery skipped', [
                'request_id' => $requestId,
                'category'   => $category,
            ]);

            return $this->wasabiAck();
        }

        // Extract indexed fields for efficient querying by third parties
        $referenceId     = $this->extractReferenceId($category, $payload);
        $merchantOrderNo = isset($payload['merchantOrderNo']) ? (string) $payload['merchantOrderNo'] : null;
        $status          = isset($payload['status'])
            ? (string) $payload['status']
            : (isset($payload['tradeStatus']) ? (string) $payload['tradeStatus'] : null);

        // Resolve the ownership token ID using the reference_id
        $resourceType = $this->resolveResourceType($category, $payload);
        $apiTokenId   = ($referenceId !== null && $resourceType !== null)
            ? $this->ownership->ownerTokenId($resourceType, (string) $referenceId)
            : null;

        // card_fee_patch events carry tradeNo as reference_id (correct for event storage)
        // but ownership must be resolved via cardNo → TenantResource::TYPE_CARD.
        if ($apiTokenId === null && $category === 'card_fee_patch' && ! empty($payload['cardNo'])) {
            $apiTokenId = $this->ownership->ownerTokenId(TenantResource::TYPE_CARD, (string) $payload['cardNo']);
        }

        WebhookEvent::create([
            'request_id'        => $requestId ? (string) $requestId : null,
            'category'          => $category,
            'reference_id'      => $referenceId !== null ? (string) $referenceId : null,
            'merchant_order_no' => $merchantOrderNo,
            'status'            => $status,
            'payload'           => $payload,
            'signature_verified' => $verified,
            'api_token_id'      => $apiTokenId,
        ]);

        // Backfill: when a card creation work-order completes, register the cardNo
        if ($category === 'work'
            && ! empty($payload['cardNo'])
            && ! empty($payload['orderNo'])
            && $apiTokenId !== null
        ) {
            $this->ownership->register(
                $apiTokenId,
                TenantResource::TYPE_CARD,
                (string) $payload['cardNo'],
                $merchantOrderNo,
            );
        }

        // Deposit fee: charge when a wallet top-up confirms
        if (in_array($category, ['wallet_transaction', 'wallet_transaction_v2'], strict: true)
            && ($payload['status'] ?? '') === 'success'
            && $apiTokenId !== null
            && ! empty($payload['receivedAmount'])
        ) {
            $this->feeService->collectDepositFee(
                $apiTokenId,
                (string) ($payload['orderNo'] ?? ''),
                (float) $payload['receivedAmount'],
            );
        }

        // -----------------------------------------------------------------------
        // Client virtual balance — mirror every money movement from Wasabi
        // -----------------------------------------------------------------------
        if ($apiTokenId !== null) {
            $this->applyBalanceEvent($category, $payload, $apiTokenId);
        }

        Log::channel('wasabi')->info('Webhook: event stored', [
            'category'     => $category,
            'request_id'   => $requestId,
            'reference_id' => $referenceId,
            'status'       => $status,
            'api_token_id' => $apiTokenId,
        ]);

        return $this->wasabiAck();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Return the mandatory Wasabi acknowledgement.
     *
     * Wasabi requires exactly this structure with code=200.
     * Any other response triggers the retry sequence.
     */
    private function wasabiAck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code'    => 200,
            'msg'     => null,
            'data'    => null,
        ]);
    }

    /**
     * Apply a balance event to the client's virtual ledger.
     *
     * Maps incoming Wasabi webhook categories/statuses to balance mutations:
     *
     *   wallet_transaction / wallet_transaction_v2 (DEPOSIT, success)
     *       → creditDeposit (deposit fee deducted inside service)
     *
     *   card_transaction (withdraw, success)
     *       → creditCardWithdraw
     *
     *   card_transaction (cancel + subType=REFUND, success)
     *       → creditCardCancelRefund
     *
     *   card_transaction (card_create or card_deposit, success)
     *       → confirmPending (pre-auth was reserved at API call time)
     *
     *   card_transaction (card_create or card_deposit, fail)
     *       → reversePending (refund the reserved amount)
     *
     *   card_fee_patch (card_patch_fee, success)
     *       → debitAuthFee
     *
     *   card_fee_patch (card_patch_cross_border, success)
     *       → debitAuthFee (cross_border_fee event)
     *
     *   card_transaction (overdraft_statement)
     *       → debitOverdraft
     *
     * @param  string               $category
     * @param  array<string, mixed> $payload
     * @param  int                  $apiTokenId
     */
    private function applyBalanceEvent(string $category, array $payload, int $apiTokenId): void
    {
        try {
            $status  = strtolower((string) ($payload['status'] ?? ''));
            $orderNo = (string) ($payload['orderNo'] ?? '');
            $merchantOrderNo = (string) ($payload['merchantOrderNo'] ?? '');

            // ---- Crypto deposit → credit client ----------------------------------------
            // wallet_transaction (v1) uses type=chain_deposit; v2 uses type=DEPOSIT.
            // Both map to the same credit action; WITHDRAW events in v2 must be excluded.
            $walletEventType = strtolower((string) ($payload['type'] ?? ''));
            if (in_array($category, ['wallet_transaction', 'wallet_transaction_v2'], strict: true)
                && $status === 'success'
                && ! empty($payload['receivedAmount'])
                && in_array($walletEventType, ['deposit', 'chain_deposit'], true)
            ) {
                $this->clientBalance->creditDeposit(
                    $apiTokenId,
                    $orderNo,
                    (float) $payload['receivedAmount'],
                );
                return;
            }

            // ---- Card operations --------------------------------------------------------
            if ($category === 'card_transaction') {
                $type    = strtolower((string) ($payload['type'] ?? ''));
                $subType = strtoupper((string) ($payload['subType'] ?? 'DEFAULT'));
                $amount  = (float) ($payload['amount'] ?? 0);
                $ref     = $orderNo ?: $merchantOrderNo;

                // Actual wallet inflow for credits = receivedAmount when available; fallback to amount.
                // This matters when Wasabi charges a withdrawal/cancel fee (fee > 0).
                $creditAmount = (float) ($payload['receivedAmount'] ?? 0);
                if ($creditAmount <= 0) {
                    $creditAmount = $amount;
                }

                // Card withdraw: money returns to merchant wallet → credit client
                if ($type === 'withdraw' && $status === 'success') {
                    $this->clientBalance->creditCardWithdraw($apiTokenId, $ref, $creditAmount);
                    return;
                }

                // Card cancel with refund: remaining card balance → credit client
                if ($type === 'cancel' && $status === 'success' && $subType === 'REFUND') {
                    $this->clientBalance->creditCardCancelRefund($apiTokenId, $ref, $creditAmount);
                    return;
                }

                // Card overdraft bill — Wasabi charged the merchant reserve
                if ($type === 'overdraft_statement' && $status === 'success') {
                    $this->clientBalance->debitOverdraft($apiTokenId, $ref, $amount);
                    return;
                }

                // Card create / card deposit confirmation or failure
                if (in_array($type, ['create', 'deposit'], strict: true)) {
                    // Find pending rows for this merchantOrderNo
                    $pendingIds = ClientTransaction::where('api_token_id', $apiTokenId)
                        ->where('reference_id', $merchantOrderNo)
                        ->where('status', ClientTransaction::STATUS_PENDING)
                        ->pluck('id')
                        ->all();

                    if (! empty($pendingIds)) {
                        if ($status === 'success') {
                            $this->clientBalance->confirmPending(...$pendingIds);
                        } elseif ($status === 'fail') {
                            $this->clientBalance->reversePending(...$pendingIds);
                        }
                    }

                    // Card application fee: collected ONLY after Wasabi confirms success.
                    // If the card creation fails, no fee is charged (pending rows reversed above).
                    if ($type === 'create' && $status === 'success') {
                        $this->feeService->collectCardApplicationFee(
                            $apiTokenId,
                            $orderNo ?: $merchantOrderNo,
                        );
                    }

                    // For card deposits, the Wasabi processing fee was NOT pre-reserved
                    // (no cardTypeId available at deposit time). Debit it now from the
                    // webhook payload so the virtual balance stays aligned with the
                    // merchant wallet deduction.
                    if ($type === 'deposit' && $status === 'success') {
                        $wasabiFee = (float) ($payload['fee'] ?? 0);
                        if ($wasabiFee > 0 && ! empty($orderNo)) {
                            $this->clientBalance->debitWasabiProcessingFee($apiTokenId, $orderNo, $wasabiFee);
                        }
                    }

                    return;
                }
            }

            // ---- Auth fee / cross-border fee -------------------------------------------
            if ($category === 'card_fee_patch' && $status === 'success') {
                $tradeNo          = (string) ($payload['tradeNo'] ?? '');
                $amount           = (float)  ($payload['amount'] ?? 0);
                $type             = (string) ($payload['type'] ?? '');
                $authorizedAmount = (float)  ($payload['authorizedAmount'] ?? 0);

                $event = $type === 'card_patch_cross_border'
                    ? ClientTransaction::EVENT_CROSS_BORDER_FEE
                    : ClientTransaction::EVENT_AUTH_FEE_PATCH;

                if (! empty($tradeNo) && $amount > 0) {
                    $this->clientBalance->debitAuthFee($apiTokenId, $tradeNo, $amount, $event);

                    // Platform FX fee — only for cross-border transactions, fire-and-forget.
                    if ($type === 'card_patch_cross_border' && $authorizedAmount > 0) {
                        $this->feeService->collectFxFee($apiTokenId, $tradeNo, $authorizedAmount);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::channel('wasabi')->error('Webhook: balance event failed', [
                'category' => $category,
                'error'    => $e->getMessage(),
                'payload'  => $payload,
            ]);
        }
    }

    /**
     * Verify the X-WSB-SIGNATURE header.
     *
     * Wasabi signs the raw request body with their RSA private key
     * using SHA256withRSA. We verify using their platform public key
     * stored at config('wasabi.wsb_public_key_path').
     */
    private function verifySignature(string $rawBody, string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $publicKeyPath = (string) config('wasabi.wsb_public_key_path');

        if (! file_exists($publicKeyPath)) {
            Log::channel('wasabi')->error('Webhook: Wasabi public key file not found', [
                'path' => $publicKeyPath,
            ]);

            return false;
        }

        $publicKey = openssl_pkey_get_public((string) file_get_contents($publicKeyPath));

        if (! $publicKey) {
            Log::channel('wasabi')->error('Webhook: failed to load Wasabi public key');

            return false;
        }

        $decodedSignature = base64_decode($signature, strict: true);

        if ($decodedSignature === false) {
            return false;
        }

        // openssl_verify: 1 = valid, 0 = invalid, -1 = error
        return openssl_verify($rawBody, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Determine which TenantResource type owns the referenceId for this event.
     *
     * @param  array<string, mixed> $payload
     */
    private function resolveResourceType(string $category, array $payload): ?string
    {
        return match (true) {
            in_array($category, ['card_holder', 'card_holder_change_email'], true) => TenantResource::TYPE_CARDHOLDER,
            $category === 'physical_card'                                           => TenantResource::TYPE_CARD,
            in_array($category, ['card_transaction', 'work', 'wallet_transaction', 'wallet_transaction_v2'], true) => TenantResource::TYPE_ORDER,
            default                                                                 => null,
        };
    }

    /**
     * Extract the primary entity identifier from the payload.
     *
     * The correct field depends on the event category, because Wasabi payloads
     * often include both cardNo and orderNo at the same time (e.g. a successful
     * card_create webhook).
     *
     * Mapping:
     *   holderId  → card_holder, card_holder_change_email
     *   cardNo    → physical_card
     *   tradeNo   → card_auth_transaction, card_fee_patch, card_3ds
     *   orderNo   → card_transaction, work, wallet_transaction, wallet_transaction_v2
     *
     * @param  array<string, mixed> $payload
     */
    private function extractReferenceId(string $category, array $payload): string|int|null
    {
        return match (true) {
            in_array($category, ['card_holder', 'card_holder_change_email'], true)
                => $payload['holderId'] ?? null,

            $category === 'physical_card'
                => $payload['cardNo'] ?? null,

            in_array($category, ['card_auth_transaction', 'card_fee_patch', 'card_3ds'], true)
                => $payload['tradeNo'] ?? null,

            // card_transaction, work, wallet_transaction, wallet_transaction_v2 → use orderNo
            default
                => $payload['orderNo'] ?? $payload['tradeNo'] ?? null,
        };
    }
}
