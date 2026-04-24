<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tracks the current USD balance for a single third-party API token.
 *
 * This is the live balance snapshot — always reflects confirmed credits minus
 * confirmed+pending debits. Use client_transactions for the full audit trail.
 *
 * @property int    $id
 * @property int    $api_token_id
 * @property string $balance      Decimal(16,4) — never negative
 * @property string $currency     Always 'USD'
 */
final class ClientBalance extends Model
{
    protected $fillable = [
        'api_token_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
    ];

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ClientTransaction::class, 'api_token_id', 'api_token_id');
    }

    /**
     * Return the balance row for the given token, creating it at 0 if absent.
     */
    public static function forToken(int $apiTokenId): self
    {
        return self::firstOrCreate(
            ['api_token_id' => $apiTokenId],
            ['balance' => '0.0000', 'currency' => 'USD'],
        );
    }
}
