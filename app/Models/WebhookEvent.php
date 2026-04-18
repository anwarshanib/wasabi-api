<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores every inbound Wasabi Card webhook event.
 *
 * Wasabi POSTs to POST /api/webhook whenever an async operation completes.
 * Events are stored here so third-party clients can poll for final results.
 *
 * @property int              $id
 * @property string|null      $request_id         X-WSB-REQUEST-ID header — used for idempotency
 * @property string           $category           X-WSB-CATEGORY  e.g. card_holder, card_transaction
 * @property string|null      $reference_id       Extracted entity ID: holderId, tradeNo, orderNo, or cardNo
 * @property string|null      $merchant_order_no  merchantOrderNo from payload
 * @property string|null      $status             status or tradeStatus from payload
 * @property array            $payload            Full raw JSON payload
 * @property bool             $signature_verified Whether X-WSB-SIGNATURE RSA verification passed
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 */
final class WebhookEvent extends Model
{
    protected $fillable = [
        'request_id',
        'category',
        'reference_id',
        'merchant_order_no',
        'status',
        'payload',
        'signature_verified',
    ];

    protected $casts = [
        'payload'            => 'array',
        'signature_verified' => 'boolean',
    ];
}
