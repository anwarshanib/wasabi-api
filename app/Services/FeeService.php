<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeeLedger;
use App\Models\FeeSetting;
use App\Models\PlatformSetting;
use App\Services\WasabiCard\AccountService;
use Illuminate\Support\Facades\Log;

/**
 * Calculates and transfers platform service fees.
 *
 * All public methods are fire-and-forget — exceptions are caught, logged,
 * and recorded in fee_ledger with status=failed. They NEVER propagate to
 * the caller, so a fee failure never disrupts the client's primary request.
 */
class FeeService
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    /**
     * Collect the deposit fee when a wallet top-up confirms.
     *
     * Triggered by: WebhookController on wallet_transaction / wallet_transaction_v2
     * where status = success.
     */
    public function collectDepositFee(int $apiTokenId, string $orderNo, float $receivedAmount): void
    {
        $rate = FeeSetting::getRate(FeeSetting::TYPE_DEPOSIT);

        if ($rate === null) {
            return;
        }

        $feeAmount = round($receivedAmount * $rate / 100, 4);

        if ($feeAmount <= 0) {
            return;
        }

        $this->transfer(
            feeType:    FeeSetting::TYPE_DEPOSIT,
            baseAmount: $receivedAmount,
            feeAmount:  $feeAmount,
            referenceId: $orderNo,
            apiTokenId: $apiTokenId,
        );
    }

    /**
     * Collect the card application fee synchronously at card creation time.
     *
     * Triggered by: CardController::createCardV2() and createCardDeprecated().
     * The rate in fee_settings is a fixed dollar amount (not a percentage).
     * Fee is always sourced from the platform WALLET (Wasabi fundTransfer only
     * allows WALLET as the payer). The api_token_id in the ledger identifies
     * which third party triggered the fee.
     */
    public function collectCardApplicationFee(int $apiTokenId, string $referenceId): void
    {
        $rate = FeeSetting::getRate(FeeSetting::TYPE_CARD_APPLICATION);

        if ($rate === null) {
            return;
        }

        $feeAmount = round((float) $rate, 4);

        if ($feeAmount <= 0) {
            return;
        }

        $this->transfer(
            feeType:     FeeSetting::TYPE_CARD_APPLICATION,
            baseAmount:  $feeAmount,
            feeAmount:   $feeAmount,
            referenceId: $referenceId,
            apiTokenId:  $apiTokenId,
        );
    }

    /**
     * Collect the FX (non-USD) transaction fee when a cross-border card fee is patched.
     *
     * Triggered by: WebhookController on card_fee_patch / card_patch_cross_border / success.
     * The rate in fee_settings is a percentage applied to the authorizedAmount (USD equivalent).
     *
     * @param  int    $apiTokenId      The client whose card triggered the FX transaction.
     * @param  string $tradeNo         Wasabi's trade number — used as the reference_id.
     * @param  float  $authorizedAmount The authorized amount in USD (base for the fee %).
     */
    public function collectFxFee(int $apiTokenId, string $tradeNo, float $authorizedAmount): void
    {
        $rate = FeeSetting::getRate(FeeSetting::TYPE_FX);

        if ($rate === null) {
            return;
        }

        $feeAmount = round($authorizedAmount * $rate / 100, 4);

        if ($feeAmount <= 0) {
            return;
        }

        $this->transfer(
            feeType:    FeeSetting::TYPE_FX,
            baseAmount: $authorizedAmount,
            feeAmount:  $feeAmount,
            referenceId: $tradeNo,
            apiTokenId: $apiTokenId,
        );
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Create a fee_ledger record and execute the Wasabi fund transfer.
     *
     * On success: status → transferred
     * On failure: status → failed (exception is swallowed)
     *
     * @param string|null $payerAccountId  Override source account. When null,
     *                                     falls back to platform_settings fee_source_account_id (WALLET).
     */
    private function transfer(
        string  $feeType,
        float   $baseAmount,
        float   $feeAmount,
        string  $referenceId,
        ?int    $apiTokenId,
        ?string $payerAccountId = null,
    ): void {
        $sourceAccountId = $payerAccountId ?? PlatformSetting::get(PlatformSetting::KEY_FEE_SOURCE);
        $destAccountId   = PlatformSetting::get(PlatformSetting::KEY_FEE_DESTINATION);

        if (empty($sourceAccountId) || empty($destAccountId)) {
            Log::channel('wasabi')->error('FeeService: source or destination account not configured', [
                'fee_type'    => $feeType,
                'reference_id' => $referenceId,
            ]);

            FeeLedger::create([
                'api_token_id' => $apiTokenId,
                'fee_type'     => $feeType,
                'base_amount'  => $baseAmount,
                'fee_amount'   => $feeAmount,
                'reference_id' => $referenceId,
                'status'       => FeeLedger::STATUS_FAILED,
            ]);

            return;
        }

        $wasabiOrderNo = 'FEE_' . strtoupper($feeType) . '_' . date('YmdHis') . '_' . random_int(1000, 9999);

        $ledger = FeeLedger::create([
            'api_token_id'   => $apiTokenId,
            'fee_type'       => $feeType,
            'base_amount'    => $baseAmount,
            'fee_amount'     => $feeAmount,
            'reference_id'   => $referenceId,
            'status'         => FeeLedger::STATUS_PENDING,
            'wasabi_order_no' => $wasabiOrderNo,
        ]);

        try {
            $this->accountService->fundTransfer([
                'type'            => 'TRANSFER',
                'merchantOrderNo' => $wasabiOrderNo,
                'amount'          => (string) $feeAmount,
                'payerAccountId'  => $sourceAccountId,
                'payeeAccountId'  => $destAccountId,
            ]);

            $ledger->update([
                'status'         => FeeLedger::STATUS_TRANSFERRED,
                'transferred_at' => now(),
            ]);

            Log::channel('wasabi')->info('FeeService: fee transferred', [
                'fee_type'       => $feeType,
                'fee_amount'     => $feeAmount,
                'reference_id'   => $referenceId,
                'wasabi_order_no' => $wasabiOrderNo,
            ]);
        } catch (\Throwable $e) {
            $ledger->update(['status' => FeeLedger::STATUS_FAILED]);

            Log::channel('wasabi')->error('FeeService: fund transfer failed', [
                'fee_type'       => $feeType,
                'fee_amount'     => $feeAmount,
                'reference_id'   => $referenceId,
                'wasabi_order_no' => $wasabiOrderNo,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
