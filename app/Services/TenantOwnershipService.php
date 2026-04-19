<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantResource;
use Illuminate\Http\JsonResponse;

/**
 * Enforces per-tenant data ownership for Wasabi resources.
 *
 * Because all third parties share one Wasabi merchant account, every resource
 * must be registered here at creation time and verified here before access.
 *
 * Usage in controllers:
 *
 *   // 1. After creating a resource on Wasabi, register it:
 *   $this->ownership->register($tokenId, TenantResource::TYPE_CARD, $cardNo, $merchantOrderNo);
 *
 *   // 2. Before accessing/modifying a specific resource, assert ownership:
 *   if ($deny = $this->ownership->deny($tokenId, TenantResource::TYPE_CARD, $cardNo)) {
 *       return $deny;  // 403 JsonResponse
 *   }
 *
 *   // 3. For list post-filtering, get the owned IDs:
 *   $ownedIds = $this->ownership->ownedIds($tokenId, TenantResource::TYPE_CARD);
 */
final class TenantOwnershipService
{
    /**
     * Register a newly created Wasabi resource as owned by the given token.
     *
     * Uses updateOrCreate so duplicate calls (e.g. on retry) are idempotent.
     */
    public function register(
        int     $apiTokenId,
        string  $resourceType,
        string  $wasabiId,
        ?string $merchantOrderNo = null
    ): void {
        TenantResource::updateOrCreate(
            ['resource_type' => $resourceType, 'wasabi_id' => $wasabiId],
            ['api_token_id' => $apiTokenId, 'merchant_order_no' => $merchantOrderNo]
        );
    }

    /**
     * Return a 403 JsonResponse if the given token does NOT own the resource,
     * or null if ownership is confirmed.
     *
     * Pattern in controllers:
     *   if ($deny = $this->ownership->deny($tokenId, TYPE_CARD, $cardNo)) {
     *       return $deny;
     *   }
     */
    public function deny(int $apiTokenId, string $resourceType, string $wasabiId): ?JsonResponse
    {
        $owns = TenantResource::where('resource_type', $resourceType)
            ->where('wasabi_id', $wasabiId)
            ->where('api_token_id', $apiTokenId)
            ->exists();

        if ($owns) {
            return null;
        }

        return response()->json([
            'success' => false,
            'code'    => 403,
            'msg'     => 'Access denied: this resource does not belong to your API key.',
            'data'    => null,
        ], 403);
    }

    /**
     * Return all Wasabi IDs of a given resource type that belong to the token.
     *
     * Used to post-filter list results from Wasabi so cross-tenant data is stripped.
     *
     * @return array<int, string>
     */
    public function ownedIds(int $apiTokenId, string $resourceType): array
    {
        return TenantResource::where('api_token_id', $apiTokenId)
            ->where('resource_type', $resourceType)
            ->pluck('wasabi_id')
            ->all();
    }

    /**
     * Resolve which tenant owns a given resource (for linking webhook events).
     * Returns null if the resource is not registered.
     */
    public function ownerTokenId(string $resourceType, string $wasabiId): ?int
    {
        $resource = TenantResource::where('resource_type', $resourceType)
            ->where('wasabi_id', $wasabiId)
            ->first();

        return $resource?->api_token_id;
    }
}
