<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClientBalance;
use App\Models\ClientTransaction;
use App\Models\FeeSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages the per-client virtual balance ledger.
 *
 * All balance mutations run inside DB transactions with row-level locking
 * (lockForUpdate) so concurrent webhook callbacks or API calls cannot
 * produce a race condition.
 *
 * Public methods are grouped into three categories:
 *   1. Credit  — money coming into the client's virtual balance
 *   2. Debit   — money going out (pre-auth pending, then confirmed/reversed)
 *   3. Query   — read-only helpers for controllers and reports
 */
class ClientBalanceService
{
    // -------------------------------------------------------------------------
    // 1. CREDIT operations
    // -------------------------------------------------------------------------

    /**
     * Credit the client's balance after a confirmed crypto deposit.
     *
     * Calculates and deducts our platform deposit fee from the credited amount,
     * records both the net credit and the fee debit in client_transactions.
     *
     * @param int    $apiTokenId   Which client
     * @param string $orderNo      Wasabi orderNo (for deduplication)
     * @param float  $grossAmount  receivedAmount from Wasabi (after Wasabi's own chain fee)
     */
    public function creditDeposit(int $apiTokenId, string $orderNo, float $grossAmount): void
    {
        // Idempotency — skip if already recorded
        if (ClientTransaction::where('api_token_id', $apiTokenId)
            ->where('reference_id', $orderNo)
            ->where('event', ClientTransaction::EVENT_DEPOSIT)
            ->exists()
        ) {
            Log::channel('wasabi')->info('ClientBalanceService: deposit already credited', [
                'api_token_id' => $apiTokenId,
                'order_no'     => $orderNo,
            ]);
            return;
        }

        $feeRate   = FeeSetting::getRate(FeeSetting::TYPE_DEPOSIT);
        $feeAmount = $feeRate !== null ? round($grossAmount * $feeRate / 100, 4) : 0.0;
        $netCredit = round($grossAmount - $feeAmount, 4);

        DB::transaction(function () use ($apiTokenId, $orderNo, $grossAmount, $feeAmount, $netCredit): void {
            $clientBalance = ClientBalance::forToken($apiTokenId);
            $row = ClientBalance::where('id', $clientBalance->id)->lockForUpdate()->first();

            $before = (float) $row->balance;
            $after  = round($before + $netCredit, 4);

            $row->balance = $after;
            $row->save();

            // Record the deposit credit
            ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'credit',
                'event'          => ClientTransaction::EVENT_DEPOSIT,
                'amount'         => $netCredit,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_id'   => $orderNo,
                'status'         => ClientTransaction::STATUS_CONFIRMED,
                'meta'           => ['gross_amount' => $grossAmount, 'platform_fee' => $feeAmount],
            ]);

            // Record the fee debit separately (for clear reporting)
            if ($feeAmount > 0) {
                ClientTransaction::create([
                    'api_token_id'   => $apiTokenId,
                    'type'           => 'debit',
                    'event'          => ClientTransaction::EVENT_PLATFORM_FEE,
                    'amount'         => $feeAmount,
                    'balance_before' => $after,
                    'balance_after'  => $after, // fee was already deducted from net credit above
                    'reference_id'   => $orderNo,
                    'status'         => ClientTransaction::STATUS_CONFIRMED,
                    'meta'           => ['fee_type' => 'deposit', 'rate' => FeeSetting::getRate(FeeSetting::TYPE_DEPOSIT)],
                ]);
            }
        });

        Log::channel('wasabi')->info('ClientBalanceService: deposit credited', [
            'api_token_id' => $apiTokenId,
            'order_no'     => $orderNo,
            'gross'        => $grossAmount,
            'fee'          => $feeAmount,
            'net_credit'   => $netCredit,
        ]);
    }

    /**
     * Credit client balance when a card withdrawal (WALLET ← CARD) confirms.
     */
    public function creditCardWithdraw(int $apiTokenId, string $orderNo, float $amount): void
    {
        $this->creditIfNotExists(
            $apiTokenId,
            $orderNo,
            $amount,
            ClientTransaction::EVENT_CARD_WITHDRAW,
        );
    }

    /**
     * Credit client balance when a cancelled card's remaining balance is refunded.
     */
    public function creditCardCancelRefund(int $apiTokenId, string $orderNo, float $amount): void
    {
        $this->creditIfNotExists(
            $apiTokenId,
            $orderNo,
            $amount,
            ClientTransaction::EVENT_CARD_CANCEL_REFUND,
        );
    }

    // -------------------------------------------------------------------------
    // 2. DEBIT operations
    // -------------------------------------------------------------------------

    /**
     * Reserve (pending) a debit for card creation BEFORE calling Wasabi.
     *
     * Reserves: card deposit amount + Wasabi card BIN fee + Wasabi deposit
     * processing fee + platform card application fee.
     * Returns ledger row IDs so the caller can confirm or reverse them all.
     *
     * @param  float $wasabiBinFee        Wasabi cardPrice (card issuance fee)
     * @param  float $wasabiProcessingFee Wasabi rechargeFeeRate-based processing fee
     * @return array{debitId: int, binFeeId: int|null, procFeeId: int|null, feeId: int|null}
     * @throws \RuntimeException if balance is insufficient
     */
    public function reserveCardCreate(
        int    $apiTokenId,
        string $merchantOrderNo,
        float  $cardAmount,
        float  $wasabiBinFee = 0.0,
        float  $wasabiProcessingFee = 0.0,
    ): array {
        $feeRate     = FeeSetting::getRate(FeeSetting::TYPE_CARD_APPLICATION);
        $platformFee = $feeRate !== null ? round((float) $feeRate, 4) : 0.0;
        $total       = round($cardAmount + $wasabiBinFee + $wasabiProcessingFee + $platformFee, 4);

        return DB::transaction(function () use (
            $apiTokenId, $merchantOrderNo, $cardAmount,
            $wasabiBinFee, $wasabiProcessingFee, $platformFee, $total,
        ): array {
            $row = ClientBalance::where('api_token_id', $apiTokenId)->lockForUpdate()->first();

            if ($row === null || (float) $row->balance < $total) {
                throw new \RuntimeException(sprintf(
                    'Insufficient balance. Required: %.4f, Available: %.4f',
                    $total,
                    (float) ($row?->balance ?? 0),
                ));
            }

            $before    = (float) $row->balance;
            $afterCard = round($before - $cardAmount, 4);
            $afterBin  = round($afterCard - $wasabiBinFee, 4);
            $afterProc = round($afterBin - $wasabiProcessingFee, 4);
            $afterFee  = round($afterProc - $platformFee, 4);

            $row->balance = $afterFee;
            $row->save();

            $debit = ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'debit',
                'event'          => ClientTransaction::EVENT_CARD_CREATE,
                'amount'         => $cardAmount,
                'balance_before' => $before,
                'balance_after'  => $afterCard,
                'reference_id'   => $merchantOrderNo,
                'status'         => ClientTransaction::STATUS_PENDING,
                'meta'           => [
                    'total_reserved'    => $total,
                    'platform_fee'      => $platformFee,
                    'wasabi_bin_fee'    => $wasabiBinFee,
                    'wasabi_proc_fee'   => $wasabiProcessingFee,
                ],
            ]);

            $binFeeRecord = null;
            if ($wasabiBinFee > 0) {
                $binFeeRecord = ClientTransaction::create([
                    'api_token_id'   => $apiTokenId,
                    'type'           => 'debit',
                    'event'          => ClientTransaction::EVENT_WASABI_CARD_FEE,
                    'amount'         => $wasabiBinFee,
                    'balance_before' => $afterCard,
                    'balance_after'  => $afterBin,
                    'reference_id'   => $merchantOrderNo,
                    'status'         => ClientTransaction::STATUS_PENDING,
                    'meta'           => ['fee_type' => 'wasabi_bin'],
                ]);
            }

            $procFeeRecord = null;
            if ($wasabiProcessingFee > 0) {
                $procFeeRecord = ClientTransaction::create([
                    'api_token_id'   => $apiTokenId,
                    'type'           => 'debit',
                    'event'          => ClientTransaction::EVENT_WASABI_PROCESSING_FEE,
                    'amount'         => $wasabiProcessingFee,
                    'balance_before' => $afterBin,
                    'balance_after'  => $afterProc,
                    'reference_id'   => $merchantOrderNo,
                    'status'         => ClientTransaction::STATUS_PENDING,
                    'meta'           => ['fee_type' => 'wasabi_processing'],
                ]);
            }

            $platformFeeRecord = null;
            if ($platformFee > 0) {
                $platformFeeRecord = ClientTransaction::create([
                    'api_token_id'   => $apiTokenId,
                    'type'           => 'debit',
                    'event'          => ClientTransaction::EVENT_PLATFORM_FEE,
                    'amount'         => $platformFee,
                    'balance_before' => $afterProc,
                    'balance_after'  => $afterFee,
                    'reference_id'   => $merchantOrderNo,
                    'status'         => ClientTransaction::STATUS_PENDING,
                    'meta'           => ['fee_type' => 'card_application'],
                ]);
            }

            return [
                'debitId'   => $debit->id,
                'binFeeId'  => $binFeeRecord?->id,
                'procFeeId' => $procFeeRecord?->id,
                'feeId'     => $platformFeeRecord?->id,
            ];
        });
    }

    /**
     * Reserve (pending) a debit for a card top-up BEFORE calling Wasabi.
     *
     * @return int  The client_transaction id for confirmation/reversal
     * @throws \RuntimeException if balance is insufficient
     */
    public function reserveCardDeposit(int $apiTokenId, string $merchantOrderNo, float $amount): int
    {
        return DB::transaction(function () use ($apiTokenId, $merchantOrderNo, $amount): int {
            $row = ClientBalance::where('api_token_id', $apiTokenId)->lockForUpdate()->first();

            if ($row === null || (float) $row->balance < $amount) {
                throw new \RuntimeException(sprintf(
                    'Insufficient balance. Required: %.4f, Available: %.4f',
                    $amount,
                    (float) ($row?->balance ?? 0),
                ));
            }

            $before = (float) $row->balance;
            $after  = round($before - $amount, 4);

            $row->balance = $after;
            $row->save();

            $record = ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'debit',
                'event'          => ClientTransaction::EVENT_CARD_DEPOSIT,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_id'   => $merchantOrderNo,
                'status'         => ClientTransaction::STATUS_PENDING,
            ]);

            return $record->id;
        });
    }

    /**
     * Confirm one or more pending debit rows (Wasabi confirmed success).
     */
    public function confirmPending(int ...$transactionIds): void
    {
        ClientTransaction::whereIn('id', $transactionIds)
            ->where('status', ClientTransaction::STATUS_PENDING)
            ->update(['status' => ClientTransaction::STATUS_CONFIRMED]);
    }

    /**
     * Reverse one or more pending debits (Wasabi reported failure).
     * Adds the amount back to the live balance.
     */
    public function reversePending(int ...$transactionIds): void
    {
        DB::transaction(function () use ($transactionIds): void {
            $rows = ClientTransaction::whereIn('id', $transactionIds)
                ->where('status', ClientTransaction::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            foreach ($rows as $tx) {
                $clientBalance = ClientBalance::where('api_token_id', $tx->api_token_id)->lockForUpdate()->first();

                if ($clientBalance === null) {
                    continue;
                }

                $before = (float) $clientBalance->balance;
                $after  = round($before + (float) $tx->amount, 4);
                $clientBalance->balance = $after;
                $clientBalance->save();

                $tx->status = ClientTransaction::STATUS_REVERSED;
                $tx->save();

                Log::channel('wasabi')->info('ClientBalanceService: pending debit reversed', [
                    'tx_id'        => $tx->id,
                    'api_token_id' => $tx->api_token_id,
                    'amount'       => $tx->amount,
                    'event'        => $tx->event,
                ]);
            }
        });
    }

    /**
     * Debit the client for Wasabi's deposit processing fee on a card top-up.
     *
     * Called from the card_transaction webhook when type=deposit, status=success
     * and the payload carries a non-zero fee.  Idempotent via (orderNo, event).
     */
    public function debitWasabiProcessingFee(int $apiTokenId, string $orderNo, float $fee): void
    {
        if ($fee <= 0) {
            return;
        }

        $this->debitAuthFee($apiTokenId, $orderNo, $fee, ClientTransaction::EVENT_WASABI_PROCESSING_FEE);
    }

    /**
     * Debit the client for an auth fee or cross-border fee from a card_fee_patch webhook.
     * These are charged directly by Wasabi against the merchant wallet — we mirror it.
     */
    public function debitAuthFee(
        int    $apiTokenId,
        string $tradeNo,
        float  $amount,
        string $event, // ClientTransaction::EVENT_AUTH_FEE_PATCH | EVENT_CROSS_BORDER_FEE
    ): void {
        if (ClientTransaction::where('api_token_id', $apiTokenId)->where('reference_id', $tradeNo)->where('event', $event)->exists()) {
            return; // idempotent
        }

        DB::transaction(function () use ($apiTokenId, $tradeNo, $amount, $event): void {
            $row = ClientBalance::where('api_token_id', $apiTokenId)->lockForUpdate()->firstOrNew(
                ['api_token_id' => $apiTokenId],
                ['balance' => '0.0000', 'currency' => 'USD'],
            );

            if (! $row->exists) {
                $row->save();
            }

            $before = (float) $row->balance;
            $after  = max(0, round($before - $amount, 4)); // floor at 0 — Wasabi already charged

            $row->balance = $after;
            $row->save();

            ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'debit',
                'event'          => $event,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_id'   => $tradeNo,
                'status'         => ClientTransaction::STATUS_CONFIRMED,
            ]);
        });
    }

    /**
     * Debit the client for an overdraft bill (card went negative, Wasabi charged merchant).
     */
    public function debitOverdraft(int $apiTokenId, string $orderNo, float $amount): void
    {
        if (ClientTransaction::where('api_token_id', $apiTokenId)->where('reference_id', $orderNo)->where('event', ClientTransaction::EVENT_OVERDRAFT)->exists()) {
            return;
        }

        DB::transaction(function () use ($apiTokenId, $orderNo, $amount): void {
            $row = ClientBalance::forToken($apiTokenId);
            $locked = ClientBalance::where('id', $row->id)->lockForUpdate()->first();

            $before = (float) $locked->balance;
            $after  = max(0, round($before - $amount, 4));

            $locked->balance = $after;
            $locked->save();

            ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'debit',
                'event'          => ClientTransaction::EVENT_OVERDRAFT,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_id'   => $orderNo,
                'status'         => ClientTransaction::STATUS_CONFIRMED,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // 3. QUERY helpers
    // -------------------------------------------------------------------------

    /**
     * Return the current available balance for a client.
     */
    public function getBalance(int $apiTokenId): float
    {
        $row = ClientBalance::where('api_token_id', $apiTokenId)->first();
        return $row !== null ? (float) $row->balance : 0.0;
    }

    /**
     * Check whether the client has sufficient balance.
     */
    public function hasSufficientBalance(int $apiTokenId, float $required): bool
    {
        return $this->getBalance($apiTokenId) >= $required;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function creditIfNotExists(
        int    $apiTokenId,
        string $referenceId,
        float  $amount,
        string $event,
    ): void {
        if (ClientTransaction::where('api_token_id', $apiTokenId)->where('reference_id', $referenceId)->where('event', $event)->exists()) {
            return;
        }

        DB::transaction(function () use ($apiTokenId, $referenceId, $amount, $event): void {
            $row    = ClientBalance::forToken($apiTokenId);
            $locked = ClientBalance::where('id', $row->id)->lockForUpdate()->first();

            $before = (float) $locked->balance;
            $after  = round($before + $amount, 4);

            $locked->balance = $after;
            $locked->save();

            ClientTransaction::create([
                'api_token_id'   => $apiTokenId,
                'type'           => 'credit',
                'event'          => $event,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_id'   => $referenceId,
                'status'         => ClientTransaction::STATUS_CONFIRMED,
            ]);
        });
    }
}
