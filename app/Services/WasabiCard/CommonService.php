<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

/**
 * Wraps the Wasabi Card COMMON API endpoints.
 *
 * Reference data (regions, cities, mobile codes) is cached locally using
 * the configured file-cache driver, as recommended by the Wasabi docs:
 * "This interface is updated very infrequently, so please localise the data
 * before calling it."
 */
final class CommonService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Return all supported countries / regions (ISO 3166-1).
     *
     * Cached for 24 hours by default (WASABI_CACHE_REGIONS in .env).
     *
     * @return array<int, array{code: string, standardCode: string, name: string}>
     */
    public function getRegions(): array
    {
        return Cache::remember(
            'wasabi:common:regions',
            config('wasabi.cache.regions'),
            fn (): array => $this->client->post('/merchant/core/mcb/common/region')['data'],
        );
    }

    /**
     * Return a flat list of cities, optionally filtered by country/region code.
     *
     * @param  string|null  $regionCode  ISO 3166-1 alpha-2 (e.g. "AU")
     * @return array<int, array{code: string, name: string, country: string}>
     */
    public function getCities(?string $regionCode = null): array
    {
        $cacheKey = $regionCode
            ? 'wasabi:common:cities:' . strtoupper($regionCode)
            : 'wasabi:common:cities:all';

        $body = $regionCode ? ['regionCode' => $regionCode] : [];

        return Cache::remember(
            $cacheKey,
            config('wasabi.cache.regions'),
            fn (): array => $this->client->post('/merchant/core/mcb/common/city', $body)['data'],
        );
    }

    /**
     * Return cities with hierarchical province/state → city structure,
     * optionally filtered by country/region code.
     *
     * @param  string|null  $regionCode  ISO 3166-1 alpha-2 (e.g. "AU")
     * @return array<int, array{code: string, name: string, parentCode: string, country: string, countryStandardCode: string, children: array}>
     */
    public function getCitiesHierarchical(?string $regionCode = null): array
    {
        $cacheKey = $regionCode
            ? 'wasabi:common:cities:hierarchical:' . strtoupper($regionCode)
            : 'wasabi:common:cities:hierarchical:all';

        $body = $regionCode ? ['regionCode' => $regionCode] : [];

        return Cache::remember(
            $cacheKey,
            config('wasabi.cache.regions'),
            fn (): array => $this->client->post('/merchant/core/mcb/common/v2/city', $body)['data'],
        );
    }

    /**
     * Return all supported mobile dialling codes.
     *
     * Cached for 24 hours. Source: Wasabi Card /merchant/core/mcb/common/mobileAreaCode.
     *
     * @return array<int, array{code: string, name: string, areaCode: string, language: string, enableGlobalTransfer: bool}>
     */
    public function getMobileCodes(): array
    {
        return Cache::remember(
            'wasabi:common:mobile-codes',
            config('wasabi.cache.regions'),
            fn (): array => $this->client->post('/merchant/core/mcb/common/mobileAreaCode')['data'],
        );
    }

    /**
     * Upload a file to the Wasabi Card platform.
     *
     * Not cached — every call produces a new fileId.
     * Supported formats: jpg, jpeg, png, pdf. Max size: 2 MB.
     *
     * @return array{fileId: string}
     */
    public function uploadFile(UploadedFile $file): array
    {
        /** @var array{fileId: string} $data */
        $data = $this->client->postMultipart('/merchant/core/mcb/common/file/upload', $file)['data'];

        return $data;
    }
}
