<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable double-entry ledger row for a single balance movement.
 *
 * Every credit or debit against a client's virtual balance is recorded here.
 * Rows are never updated after creation except for status transitions:
 *   pending → confirmed   (Wasabi webhook confirmed success)
 *   pending → reversed    (Wasabi webhook reported failure)
 *
 * @property int    $id
 * @property int    $api_token_id
 * @property string $type          'credit' | 'debit'
 * @property string $event         see migration for full list
 * @property string $amount        Decimal(16,4) — always positive
 * @property string $balance_before
 * @property string $balance_after
 * @property string|null $reference_id  Wasabi orderNo/tradeNo/cardNo
 * @property string $currency
 * @property string $status        'pending' | 'confirmed' | 'reversed'
 * @property array|null  $meta
 */
final class ClientTransaction extends Model
{
    // Event constants — what caused the balance movement
    public const EVENT_DEPOSIT           = 'deposit';
    public const EVENT_CARD_CREATE       = 'card_create';
    public const EVENT_CARD_DEPOSIT      = 'card_deposit';
    public const EVENT_CARD_WITHDRAW     = 'card_withdraw';
    public const EVENT_CARD_CANCEL_REFUND = 'card_cancel_refund';
    public const EVENT_PLATFORM_FEE      = 'platform_fee';
    public const EVENT_AUTH_FEE_PATCH    = 'auth_fee_patch';
    public const EVENT_CROSS_BORDER_FEE  = 'cross_border_fee';
    public const EVENT_OVERDRAFT             = 'overdraft';
    public const EVENT_ADJUSTMENT            = 'adjustment';
    /** Wasabi's card issuance / BIN fee (cardPrice). Debited at card creation. */
    public const EVENT_WASABI_CARD_FEE       = 'wasabi_card_fee';
    /** Wasabi's deposit processing fee (rechargeFeeRate). Debited at create/deposit. */
    public const EVENT_WASABI_PROCESSING_FEE = 'wasabi_processing_fee';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REVERSED  = 'reversed';

    protected $fillable = [
        'api_token_id',
        'type',
        'event',
        'amount',
        'balance_before',
        'balance_after',
        'reference_id',
        'currency',
        'status',
        'meta',
    ];

    protected $casts = [
        'amount'         => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after'  => 'decimal:4',
        'meta'           => 'array',
    ];

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }
}
