<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records every fee transfer attempt (pending / transferred / failed).
 *
 * @property int         $id
 * @property int|null    $api_token_id
 * @property string      $fee_type        deposit | card_application | fx
 * @property float       $base_amount     original transaction amount
 * @property float       $fee_amount      calculated fee amount
 * @property string      $currency
 * @property string|null $reference_id    orderNo / tradeNo
 * @property string      $status          pending | transferred | failed
 * @property string|null $wasabi_order_no our FEE_* merchantOrderNo sent to Wasabi
 * @property \Carbon\Carbon|null $transferred_at
 */
final class FeeLedger extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_FAILED      = 'failed';

    protected $table = 'fee_ledger';

    protected $fillable = [
        'api_token_id',
        'fee_type',
        'base_amount',
        'fee_amount',
        'currency',
        'reference_id',
        'status',
        'wasabi_order_no',
        'transferred_at',
    ];

    protected $casts = [
        'base_amount'    => 'float',
        'fee_amount'     => 'float',
        'transferred_at' => 'datetime',
    ];

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }
}
