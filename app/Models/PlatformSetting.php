<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store for platform-wide configuration.
 *
 * Current keys:
 *   fee_source_account_id      — Wasabi accountId to deduct fees FROM (WALLET)
 *   fee_destination_account_id — Wasabi accountId to collect fees INTO (MARGIN)
 *
 * @property int         $id
 * @property string      $key
 * @property string|null $value
 */
final class PlatformSetting extends Model
{
    public const KEY_FEE_SOURCE      = 'fee_source_account_id';
    public const KEY_FEE_DESTINATION = 'fee_destination_account_id';

    protected $fillable = ['key', 'value'];

    public static function get(string $key): ?string
    {
        $row = static::where('key', $key)->first();

        return $row?->value;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
