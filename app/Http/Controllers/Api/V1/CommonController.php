<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\CommonService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card COMMON reference endpoints to third-party clients.
 */
final class CommonController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CommonService $commonService,
    ) {}

    #[OA\Get(
        path: '/api/v1/common/regions',
        operationId: 'getRegions',
        summary: 'Country / Region list',
        description: 'Returns all supported countries and regions (ISO 3166-1). Response is cached for 24 hours. Source: Wasabi Card /merchant/core/mcb/common/region',
        security: [['ApiKeyAuth' => []]],
        tags: ['Common'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',                type: 'string',  example: 'SG',  description: 'ISO 3166-1 alpha-2'),
                                    new OA\Property(property: 'standardCode',        type: 'string',  example: 'SGP', description: 'ISO 3166-1 alpha-3'),
                                    new OA\Property(property: 'name',                type: 'string',  example: 'Singapore'),
                                    new OA\Property(property: 'enableKyc',           type: 'boolean', example: true),
                                    new OA\Property(property: 'enableCard',          type: 'boolean', example: true),
                                    new OA\Property(property: 'enableGlobalTransfer', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key',    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',     content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function regions(): JsonResponse
    {
        $regions = $this->commonService->getRegions();

        return $this->success($regions);
    }

    #[OA\Get(
        path: '/api/v1/common/cities',
        operationId: 'getCities',
        summary: 'City list',
        description: 'Returns a flat list of cities. Optionally filter by country/region code (ISO 3166-1 alpha-2). Response is cached for 24 hours. Source: Wasabi Card /merchant/core/mcb/common/city',
        security: [['ApiKeyAuth' => []]],
        tags: ['Common'],
        parameters: [
            new OA\Parameter(
                name: 'regionCode',
                in: 'query',
                required: false,
                description: 'Filter by country/region code (ISO 3166-1 alpha-2), e.g. AU',
                schema: new OA\Schema(type: 'string', example: 'AU')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',    type: 'string', example: 'AU_01',    description: 'City code'),
                                    new OA\Property(property: 'name',    type: 'string', example: 'Sydney',   description: 'City name'),
                                    new OA\Property(property: 'country', type: 'string', example: 'AU',       description: 'ISO 3166-1 alpha-2 country code'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key',    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',     content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cities(Request $request): JsonResponse
    {
        $regionCode = $request->query('regionCode') ?: null;

        $cities = $this->commonService->getCities($regionCode);

        return $this->success($cities);
    }

    #[OA\Get(
        path: '/api/v1/common/cities/hierarchical',
        operationId: 'getCitiesHierarchical',
        summary: 'City list (hierarchical relationship)',
        description: 'Returns cities grouped in a two-level hierarchy: province/state/region → city. Optionally filter by country/region code. Response is cached for 24 hours. Source: Wasabi Card /merchant/core/mcb/common/v2/city',
        security: [['ApiKeyAuth' => []]],
        tags: ['Common'],
        parameters: [
            new OA\Parameter(
                name: 'regionCode',
                in: 'query',
                required: false,
                description: 'Filter by country/region code (ISO 3166-1 alpha-2), e.g. AU',
                schema: new OA\Schema(type: 'string', example: 'AU')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',                type: 'string',  example: 'AU-ACT',                     description: 'Province/state code'),
                                    new OA\Property(property: 'name',                type: 'string',  example: 'Australian Capital Territory', description: 'Province/state name'),
                                    new OA\Property(property: 'parentCode',          type: 'string',  example: '0',                          description: '"0" for top-level province/state'),
                                    new OA\Property(property: 'country',             type: 'string',  example: 'AU',                         description: 'ISO 3166-1 alpha-2'),
                                    new OA\Property(property: 'countryStandardCode', type: 'string',  example: 'AUS',                        description: 'ISO 3166-1 alpha-3'),
                                    new OA\Property(
                                        property: 'children',
                                        type: 'array',
                                        description: 'Cities within this province/state',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'code',                type: 'string', example: 'AU-ACT-80100'),
                                                new OA\Property(property: 'name',                type: 'string', example: 'Australian Capital Territory (Canberra)'),
                                                new OA\Property(property: 'parentCode',          type: 'string', example: 'AU-ACT'),
                                                new OA\Property(property: 'country',             type: 'string', example: 'AU'),
                                                new OA\Property(property: 'countryStandardCode', type: 'string', example: 'AUS'),
                                                new OA\Property(property: 'children',            type: 'array',  items: new OA\Items(type: 'object')),
                                            ],
                                            type: 'object'
                                        )
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key',    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',     content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function citiesHierarchical(Request $request): JsonResponse
    {
        $regionCode = $request->query('regionCode') ?: null;

        $cities = $this->commonService->getCitiesHierarchical($regionCode);

        return $this->success($cities);
    }

    #[OA\Get(
        path: '/api/v1/common/mobile-codes',
        operationId: 'getMobileCodes',
        summary: 'Mobile Code List',
        description: 'Returns all supported international mobile dialling codes. Response is cached for 24 hours. Source: Wasabi Card /merchant/core/mcb/common/mobileAreaCode',
        security: [['ApiKeyAuth' => []]],
        tags: ['Common'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'code',                 type: 'string',  example: '+1',    description: 'International dialling code (e.g. +1, +61)'),
                                    new OA\Property(property: 'name',                 type: 'string',  example: 'Canada',description: 'Country name'),
                                    new OA\Property(property: 'areaCode',             type: 'string',  example: 'CA',    description: 'ISO 3166-1 alpha-2 country code'),
                                    new OA\Property(property: 'language',             type: 'string',  example: 'en_US', description: 'Default language locale'),
                                    new OA\Property(property: 'enableGlobalTransfer', type: 'boolean', example: true,    description: 'Whether global transfer is enabled for this country'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function mobileCodes(): JsonResponse
    {
        $codes = $this->commonService->getMobileCodes();

        return $this->success($codes);
    }

    #[OA\Post(
        path: '/api/v1/common/files/upload',
        operationId: 'uploadFile',
        summary: 'Upload File',
        description: 'Upload a file and receive a fileId for use in subsequent API calls (e.g. KYC document submission). Supported formats: jpg, jpeg, png, pdf. Maximum size: 2 MB. Source: Wasabi Card /merchant/core/mcb/common/file/upload',
        security: [['ApiKeyAuth' => []]],
        tags: ['Common'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'File to upload. Supported formats: jpg, jpeg, png, pdf. Maximum size: 2 MB.',
                            type: 'string',
                            format: 'binary',
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'File uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'fileId', type: 'string', example: 'c7bf3c1b-25d1-4b75-b519-1e6bf383d0a7', description: 'Uploaded file identifier for use in subsequent API calls'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key',          content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error (invalid file type or size exceeded)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',                 content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $result = $this->commonService->uploadFile($request->file('file'));

        return $this->success($result);
    }
}

