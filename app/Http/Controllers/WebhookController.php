<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
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
        $referenceId     = $this->extractReferenceId($payload);
        $merchantOrderNo = isset($payload['merchantOrderNo']) ? (string) $payload['merchantOrderNo'] : null;
        $status          = isset($payload['status'])
            ? (string) $payload['status']
            : (isset($payload['tradeStatus']) ? (string) $payload['tradeStatus'] : null);

        WebhookEvent::create([
            'request_id'        => $requestId ? (string) $requestId : null,
            'category'          => $category,
            'reference_id'      => $referenceId !== null ? (string) $referenceId : null,
            'merchant_order_no' => $merchantOrderNo,
            'status'            => $status,
            'payload'           => $payload,
            'signature_verified' => $verified,
        ]);

        Log::channel('wasabi')->info('Webhook: event stored', [
            'category'     => $category,
            'request_id'   => $requestId,
            'reference_id' => $referenceId,
            'status'       => $status,
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
     * Extract the primary entity identifier from the payload.
     *
     * Each event type uses a different field as its primary ID:
     *
     *   holderId  → card_holder, card_holder_change_email
     *   cardNo    → physical_card
     *   tradeNo   → card_auth_transaction, card_fee_patch, card_3ds
     *   orderNo   → card_transaction, work, wallet_transaction, wallet_transaction_v2
     *
     * @param  array<string, mixed> $payload
     */
    private function extractReferenceId(array $payload): string|int|null
    {
        return $payload['holderId']
            ?? $payload['cardNo']
            ?? $payload['tradeNo']
            ?? $payload['orderNo']
            ?? null;
    }
}
