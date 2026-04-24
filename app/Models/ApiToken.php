<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Represents a third-party API token issued by the admin panel.
 *
 * Security model:
 *   - Raw token is NEVER stored. Only a SHA-256 hash is used for lookup.
 *   - An encrypted copy (Laravel Crypt) is stored only so admin can reveal it once.
 *   - Middleware hashes the incoming X-API-KEY and queries token_hash.
 *
 * @property int              $id
 * @property string           $name
 * @property string|null      $email
 * @property string|null      $description
 * @property string           $token_hash        sha256(raw_token)
 * @property string           $token_encrypted   Crypt::encryptString(raw_token)
 * @property bool             $is_active
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 */
final class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'email',
        'description',
        'token_hash',
        'token_encrypted',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    // Never expose these in serialisation
    protected $hidden = [
        'token_hash',
        'token_encrypted',
    ];

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a new raw token string and return [rawToken, ApiToken model].
     * The raw token is returned once and never retrievable again from its hash.
     *
     * @return array{0: string, 1: self}
     */
    public static function generateAndCreate(string $name, ?string $email, ?string $description): array
    {
        $rawToken = 'wc_' . Str::random(48);

        $token = self::create([
            'name'            => $name,
            'email'           => $email,
            'description'     => $description,
            'token_hash'      => hash('sha256', $rawToken),
            'token_encrypted' => Crypt::encryptString($rawToken),
            'is_active'       => true,
        ]);

        return [$rawToken, $token];
    }

    /**
     * Decrypt and return the raw token (for the admin "reveal" feature).
     */
    public function decryptToken(): string
    {
        return Crypt::decryptString($this->token_encrypted);
    }

    /**
     * Find an active token record by an incoming raw API key.
     */
    public static function findByRawKey(string $rawKey): ?self
    {
        return self::where('token_hash', hash('sha256', $rawKey))
            ->where('is_active', true)
            ->first();
    }

    public function clientBalance(): HasOne
    {
        return $this->hasOne(ClientBalance::class);
    }
}
