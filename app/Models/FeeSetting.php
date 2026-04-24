<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores the fee rate configuration per fee type.
 *
 * @property int    $id
 * @property string $fee_type   deposit | card_application | fx
 * @property float  $rate       percentage (1.0 = 1 %)
 * @property bool   $is_active
 */
final class FeeSetting extends Model
{
    public const TYPE_DEPOSIT          = 'deposit';
    public const TYPE_CARD_APPLICATION = 'card_application';
    public const TYPE_FX               = 'fx';

    protected $fillable = ['fee_type', 'rate', 'is_active'];

    protected $casts = [
        'rate'      => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Return the active rate for a fee type, or null if not found / inactive.
     */
    public static function getRate(string $type): ?float
    {
        $setting = static::where('fee_type', $type)->first();

        if ($setting === null || ! $setting->is_active || $setting->rate <= 0) {
            return null;
        }

        return $setting->rate;
    }
}
