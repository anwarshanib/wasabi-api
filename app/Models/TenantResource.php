<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps a Wasabi resource to the third-party API token that created it.
 *
 * Because all third parties share one Wasabi merchant account, this table is
 * the only place that records which tenant owns which Wasabi entity.
 *
 * @property int    $id
 * @property int    $api_token_id
 * @property string $resource_type   'cardholder' | 'card' | 'wallet_address' | 'order'
 * @property string $wasabi_id       holderId / cardNo / address / orderNo
 * @property string|null $merchant_order_no
 */
final class TenantResource extends Model
{
    public const TYPE_CARDHOLDER     = 'cardholder';
    public const TYPE_CARD           = 'card';
    public const TYPE_WALLET_ADDRESS = 'wallet_address';
    public const TYPE_ORDER          = 'order';

    protected $fillable = [
        'api_token_id',
        'resource_type',
        'wasabi_id',
        'merchant_order_no',
    ];

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }
}
