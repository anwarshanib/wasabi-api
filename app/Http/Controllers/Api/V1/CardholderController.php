<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\CardholderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card CARDHOLDER endpoints to third-party clients.
 */
final class CardholderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CardholderService $cardholderService,
    ) {}

    #[OA\Post(
        path: '/api/v1/cardholders/occupations',
        operationId: 'cardholderOccupations',
        summary: 'Cardholder Occupation',
        description: "Returns all supported cardholder occupation codes.\n\nUse the returned `occupationCode` values when creating or updating a cardholder.\n\nNo request body parameters are required.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/occupations",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
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
                                    new OA\Property(property: 'occupationCode', type: 'string', example: '11-1011', description: 'Occupation code'),
                                    new OA\Property(property: 'description',    type: 'string', example: 'Chief Executives', description: 'Occupation description'),
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
    public function occupations(): JsonResponse
    {
        $result = $this->cardholderService->occupations();

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/create',
        operationId: 'createCardholderDeprecated',
        summary: 'Cardholder-Create-v1-Deprecated',
        description: "**Deprecated** — Create a new cardholder.\n\n**Restricted Countries/Regions** (cardholders from these regions cannot be created):\nCuba; North Korea; Egypt; Iran; Myanmar; Nigeria; Russia; Belarus; South Africa; Syria; Ukraine; Venezuela; Sudan; South Sudan; Libya; Crimea (Ukraine); Burundi; Central African Republic; Somalia; Zimbabwe; Afghanistan.\n\n**Supported ID Types by Country/Region**:\n- Hong Kong: `PASSPORT`, `HK_HKID`\n- Other Countries: `PASSPORT`, `DLN` (Driver's licence), `GOVERNMENT_ISSUED_ID_CARD` (ID card)\n\n`areaCode`: Mobile phone area code (length 2–5). Obtain supported values from the Support Bins API (`supportHolderAreaCode`).\n\n`nationality`: ISO 3166-1 alpha-2 nationality code. Obtain supported values from Support Bins API (`supportHolderNationality`).\n\n`country`: ISO 3166-1 alpha-2 country/region code for billing address. Obtain supported values from Support Bins API (`supportHolderRegin`).\n\n`town`: City code for billing address. Obtain from the City List API.\n\n`address`: length 2–40. Can only contain letters, numbers, hyphens and spaces. Regex: `^[A-Za-z0-9\\- ]+$`.\n\n`postCode`: length 2–15. Regex: `^[a-zA-Z0-9]{2,15}$`.\n\n`firstName` / `lastName`: Only English characters. length 2–32 each. Combined length of firstName and lastName (including space) must not exceed 32 characters.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/create",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        deprecated: true,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantOrderNo', 'cardTypeId', 'areaCode', 'mobile', 'email', 'firstName', 'lastName', 'birthday', 'nationality', 'country', 'town', 'address', 'postCode'],
                properties: [
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'ORDER202501010000000001',  description: 'Client transaction id. Length must be between 20 and 40 characters'),
                    new OA\Property(property: 'cardTypeId',      type: 'integer', example: 111002,                     description: 'Card type ID (from Support Bins API)'),
                    new OA\Property(property: 'areaCode',        type: 'string',  example: '1',                        description: 'Mobile phone area code. Length 2–5. Obtain supported values from Support Bins API (supportHolderAreaCode)'),
                    new OA\Property(property: 'mobile',          type: 'string',  example: '4155550100',               description: 'Mobile phone number. Length 5–20'),
                    new OA\Property(property: 'email',           type: 'string',  example: 'john.doe@example.com',     description: 'Email address. Receives verification code. Length 5–50'),
                    new OA\Property(property: 'firstName',       type: 'string',  example: 'John',                     description: 'First name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters (including spaces)'),
                    new OA\Property(property: 'lastName',        type: 'string',  example: 'Doe',                      description: 'Last name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters (including spaces)'),
                    new OA\Property(property: 'birthday',        type: 'string',  example: '1990-01-15',               description: 'Date of birth. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'nationality',     type: 'string',  example: 'US',                       description: 'Nationality code. ISO 3166-1 alpha-2. Obtain supported values from Support Bins API (supportHolderNationality)'),
                    new OA\Property(property: 'country',         type: 'string',  example: 'US',                       description: 'Country/Region code for billing address. ISO 3166-1 alpha-2. Obtain supported values from Support Bins API (supportHolderRegin)'),
                    new OA\Property(property: 'town',            type: 'string',  example: 'LA',                       description: 'City code for billing address. Obtain from the City List API (code field)'),
                    new OA\Property(property: 'address',         type: 'string',  example: '123 Main Street',          description: 'Billing address. Length 2–40. Letters, numbers, hyphens and spaces only. Regex: ^[A-Za-z0-9\\- ]+$'),
                    new OA\Property(property: 'postCode',        type: 'string',  example: '90001',                    description: 'Postal code for billing. Length 2–15. Regex: ^[a-zA-Z0-9]{2,15}$'),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'holderId',           type: 'integer', example: 124024,                        description: 'Cardholder id'),
                                new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: '114242059249029235245352442',  nullable: true, description: 'Client transaction id'),
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 124024,                        description: 'Card type id'),
                                new OA\Property(property: 'statusFlowLocation', type: 'string',  example: 'admin',                       description: 'Review flow location. admin: platform review'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit',                  description: 'Cardholder status'),
                                new OA\Property(property: 'description',        type: 'string',  example: 'SUCCESS',                     description: 'Description'),
                                new OA\Property(property: 'respMsg',            type: 'string',  example: 'SUCCESS',                     description: 'Response message'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function createCardholderDeprecated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo' => ['required', 'string', 'min:20', 'max:40'],
            'cardTypeId'      => ['required', 'integer'],
            'areaCode'        => ['required', 'string', 'min:2', 'max:5'],
            'mobile'          => ['required', 'string', 'min:5', 'max:20'],
            'email'           => ['required', 'string', 'email', 'max:50'],
            'firstName'       => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'lastName'        => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'birthday'        => ['required', 'date_format:Y-m-d'],
            'nationality'     => ['required', 'string', 'size:2'],
            'country'         => ['required', 'string', 'size:2'],
            'town'            => ['required', 'string'],
            'address'         => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'postCode'        => ['required', 'string', 'min:2', 'max:15', 'regex:/^[a-zA-Z0-9]{2,15}$/'],
        ]);

        $result = $this->cardholderService->createCardholderDeprecated($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/update',
        operationId: 'updateCardholderDeprecated',
        summary: 'Cardholder-Update-v1-Deprecated',
        description: "**Deprecated** — Update an existing cardholder's information.\n\n`holderId`: The cardholder ID returned from the Create Cardholder API.\n\n`areaCode`: Mobile phone area code (length 2–5). Obtain supported values from the Support Bins API (`supportHolderAreaCode`).\n\n`nationality`: ISO 3166-1 alpha-2 nationality code. Obtain supported values from Support Bins API (`supportHolderNationality`).\n\n`country`: ISO 3166-1 alpha-2 country/region code for billing address. Obtain supported values from Support Bins API (`supportHolderRegin`).\n\n`town`: City code for billing address. Obtain from the City List API.\n\n`address`: length 2–40. Can only contain letters, numbers, hyphens and spaces. Regex: `^[A-Za-z0-9\\- ]+$`.\n\n`postCode`: length 2–15. Regex: `^[a-zA-Z0-9]{2,15}$`.\n\n`firstName` / `lastName`: Only English characters. length 2–32 each. Combined length must not exceed 32 characters.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/update",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        deprecated: true,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['holderId', 'areaCode', 'mobile', 'email', 'firstName', 'lastName', 'birthday', 'nationality', 'country', 'town', 'address', 'postCode'],
                properties: [
                    new OA\Property(property: 'holderId',    type: 'integer', example: 124024,              description: 'Holder id (from Create Cardholder response)'),
                    new OA\Property(property: 'areaCode',    type: 'string',  example: '1',                 description: 'Mobile phone area code. Length 2–5. Obtain supported values from Support Bins API (supportHolderAreaCode)'),
                    new OA\Property(property: 'mobile',      type: 'string',  example: '4155550100',        description: 'Mobile phone number. Length 5–20'),
                    new OA\Property(property: 'email',       type: 'string',  example: 'john.doe@example.com', description: 'Email address. Receives verification code. Length 5–50'),
                    new OA\Property(property: 'firstName',   type: 'string',  example: 'John',              description: 'First name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters (including spaces)'),
                    new OA\Property(property: 'lastName',    type: 'string',  example: 'Doe',               description: 'Last name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters (including spaces)'),
                    new OA\Property(property: 'birthday',    type: 'string',  example: '1990-01-15',        description: 'Date of birth. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'nationality', type: 'string',  example: 'US',                description: 'Nationality code. ISO 3166-1 alpha-2. Obtain supported values from Support Bins API (supportHolderNationality)'),
                    new OA\Property(property: 'country',     type: 'string',  example: 'US',                description: 'Country/Region code for billing address. ISO 3166-1 alpha-2. Obtain supported values from Support Bins API (supportHolderRegin)'),
                    new OA\Property(property: 'town',        type: 'string',  example: 'LA',                description: 'City code for billing address. Obtain from the City List API (code field)'),
                    new OA\Property(property: 'address',     type: 'string',  example: '123 Main Street',   description: 'Billing address. Length 2–40. Letters, numbers, hyphens and spaces only. Regex: ^[A-Za-z0-9\\- ]+$'),
                    new OA\Property(property: 'postCode',    type: 'string',  example: '90001',             description: 'Postal code for billing. Length 2–15. Regex: ^[a-zA-Z0-9]{2,15}$'),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'holderId',           type: 'integer', example: 124024,                        description: 'Cardholder id'),
                                new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: '114242059249029235245352442',  nullable: true, description: 'Client transaction id'),
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 124024,                        description: 'Card type id'),
                                new OA\Property(property: 'statusFlowLocation', type: 'string',  example: 'admin',                       description: 'Review flow location. admin: platform review'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit',                  description: 'Cardholder status'),
                                new OA\Property(property: 'description',        type: 'string',  example: 'SUCCESS',                     description: 'Description'),
                                new OA\Property(property: 'respMsg',            type: 'string',  example: 'SUCCESS',                     description: 'Response message'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateCardholderDeprecated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holderId'    => ['required', 'integer'],
            'areaCode'    => ['required', 'string', 'min:2', 'max:5'],
            'mobile'      => ['required', 'string', 'min:5', 'max:20'],
            'email'       => ['required', 'string', 'email', 'max:50'],
            'firstName'   => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'lastName'    => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'birthday'    => ['required', 'date_format:Y-m-d'],
            'nationality' => ['required', 'string', 'size:2'],
            'country'     => ['required', 'string', 'size:2'],
            'town'        => ['required', 'string'],
            'address'     => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'postCode'    => ['required', 'string', 'min:2', 'max:15', 'regex:/^[a-zA-Z0-9]{2,15}$/'],
        ]);

        $result = $this->cardholderService->updateCardholderDeprecated($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/create-v2',
        operationId: 'createCardholderV2',
        summary: 'Cardholder-Create-v2',
        description: "Create a new cardholder using the V2 endpoint.\n\n**Important**: Cardholder information cannot be modified after submission. Email and ID number are globally unique per card type — a duplicate submitted from a different channel will be rejected.\n\n**cardHolderModel values**:\n- `B2B`: Standard model. Common fields only (no KYC documents required).\n- `B2C`: Extended model. All B2B fields plus `nationality`, `gender`, `occupation`, `annualSalary`, `accountPurpose`, `expectedMonthlyVolume`, `idType`, `idNumber`, `issueDate`, `idNoExpiryDate`, `idFrontId`, `idBackId`, `idHoldId`, `ipAddress`. Optional `kycVerification` object (required for card type 111065).\n\n**Supported ID types**: `PASSPORT`, `HK_HKID` (Hong Kong only), `DLN` (Driver\'s license), `GOVERNMENT_ISSUED_ID_CARD`.\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/v2/create",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardHolderModel', 'merchantOrderNo', 'cardTypeId', 'areaCode', 'mobile', 'email', 'firstName', 'lastName', 'birthday', 'country', 'town', 'address', 'postCode'],
                properties: [
                    new OA\Property(property: 'cardHolderModel',        type: 'string',  example: 'B2C',                      description: 'Cardholder business model. Values: B2B, B2C'),
                    new OA\Property(property: 'merchantOrderNo',        type: 'string',  example: 'ORDER202501010000000001',  description: 'Client transaction id. Length must be between 20 and 40 characters'),
                    new OA\Property(property: 'cardTypeId',             type: 'integer', example: 111002,                    description: 'Card type ID (from Support Bins API)'),
                    new OA\Property(property: 'areaCode',               type: 'string',  example: '1',                       description: 'Mobile area code. Length 2–5. Obtain from Support Bins API (supportHolderAreaCode)'),
                    new OA\Property(property: 'mobile',                 type: 'string',  example: '4155550100',              description: 'Mobile phone number. Length 5–20'),
                    new OA\Property(property: 'email',                  type: 'string',  example: 'john.doe@example.com',    description: 'Email. Receives verification code. Length 5–50'),
                    new OA\Property(property: 'firstName',              type: 'string',  example: 'John',                    description: 'First name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters'),
                    new OA\Property(property: 'lastName',               type: 'string',  example: 'Doe',                     description: 'Last name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters'),
                    new OA\Property(property: 'birthday',               type: 'string',  example: '1990-01-15',              description: 'Date of birth. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'country',                type: 'string',  example: 'US',                      description: 'Country/Region code for billing address. ISO 3166-1 alpha-2. Obtain from Support Bins API (supportHolderRegin)'),
                    new OA\Property(property: 'town',                   type: 'string',  example: 'LA',                      description: 'City code for billing address. Obtain from City List API (code field)'),
                    new OA\Property(property: 'address',                type: 'string',  example: '123 Main Street',         description: 'Billing address. Length 2–40. Letters, numbers, hyphens and spaces only. Regex: ^[A-Za-z0-9\\- ]+$'),
                    new OA\Property(property: 'postCode',               type: 'string',  example: '90001',                   description: 'Postal code. Length 2–15. Regex: ^[a-zA-Z0-9]{1,15}$'),
                    new OA\Property(property: 'nationality',            type: 'string',  example: 'US',                      description: '[B2C only] Nationality code. ISO 3166-1 alpha-2. Obtain from Support Bins API (supportHolderNationality)'),
                    new OA\Property(property: 'gender',                 type: 'string',  example: 'M',                       description: '[B2C only] Gender. M: male, F: female'),
                    new OA\Property(property: 'occupation',             type: 'string',  example: '11-1011',                 description: '[B2C only] Occupation code. Obtain from Cardholder Occupation API (occupationCode)'),
                    new OA\Property(property: 'annualSalary',           type: 'string',  example: '100000 USD',              description: '[B2C only] Annual salary. Example: 100000 USD'),
                    new OA\Property(property: 'accountPurpose',         type: 'string',  example: 'Living Expense',          description: '[B2C only] Account purpose. English only'),
                    new OA\Property(property: 'expectedMonthlyVolume',  type: 'string',  example: '10000 USD',               description: '[B2C only] Expected monthly trading volume. Example: 10000 USD'),
                    new OA\Property(property: 'idType',                 type: 'string',  example: 'PASSPORT',                description: '[B2C only] ID type. Values: PASSPORT, HK_HKID (Hong Kong), DLN (Driver\'s license), GOVERNMENT_ISSUED_ID_CARD'),
                    new OA\Property(property: 'idNumber',               type: 'string',  example: 'A12345678',               description: '[B2C only] ID number. Length 2–50'),
                    new OA\Property(property: 'issueDate',              type: 'string',  example: '2020-01-01',              description: '[B2C only] ID document issuance date. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'idNoExpiryDate',         type: 'string',  example: '2030-01-01',              description: '[B2C only] ID document expiration date. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'idFrontId',              type: 'string',  example: 'file_abc123',             description: '[B2C only] Front photo of ID card file ID. Obtain from Upload File API'),
                    new OA\Property(property: 'idBackId',               type: 'string',  example: 'file_def456',             description: '[B2C only] Back photo of ID card file ID. Obtain from Upload File API. If no back view, reuse idFrontId'),
                    new OA\Property(property: 'idHoldId',               type: 'string',  example: 'file_ghi789',             description: '[B2C only] User face/selfie photo file ID. Obtain from Upload File API'),
                    new OA\Property(property: 'ipAddress',              type: 'string',  example: '192.168.1.1',             description: '[B2C only] Client IPv4 address'),
                    new OA\Property(
                        property: 'kycVerification',
                        type: 'object',
                        description: '[B2C only, optional] KYC verification credentials. Required for card type 111065',
                        properties: [
                            new OA\Property(property: 'provider',    type: 'string', example: 'SUMSUB',  description: 'Third-party KYC provider name. Example: SUMSUB'),
                            new OA\Property(property: 'referenceId', type: 'string', example: 'abc123',  description: 'Third-party KYC provider referenceId. For SUMSUB, pass the applicantId'),
                        ]
                    ),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'holderId',           type: 'integer', example: 124024,                       description: 'Cardholder id'),
                                new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: '114242059249029235245352442', nullable: true, description: 'Client transaction id'),
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 124024,                       description: 'Card type id'),
                                new OA\Property(property: 'statusFlowLocation', type: 'string',  example: 'admin',                      description: 'Review flow location. Process: Platform review first, then bank review. admin: platform review; channel: bank review'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit',                 description: 'Status: wait_audit (Pending), pass_audit (Approved), under_review (In review), reject (Rejected)'),
                                new OA\Property(property: 'description',        type: 'string',  example: 'SUCCESS', nullable: true,    description: 'Description'),
                                new OA\Property(property: 'respMsg',            type: 'string',  example: 'SUCCESS', nullable: true,    description: 'Remark. Deprecated, will be removed'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function createCardholderV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardHolderModel'       => ['required', 'string', 'in:B2B,B2C'],
            'merchantOrderNo'       => ['required', 'string', 'min:20', 'max:40'],
            'cardTypeId'            => ['required', 'integer'],
            'areaCode'              => ['required', 'string', 'min:2', 'max:5'],
            'mobile'                => ['required', 'string', 'min:5', 'max:20'],
            'email'                 => ['required', 'string', 'email', 'max:50'],
            'firstName'             => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'lastName'              => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'birthday'              => ['required', 'date_format:Y-m-d'],
            'country'               => ['required', 'string', 'size:2'],
            'town'                  => ['required', 'string'],
            'address'               => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'postCode'              => ['required', 'string', 'min:2', 'max:15', 'regex:/^[a-zA-Z0-9]{1,15}$/'],
            // B2C-only fields
            'nationality'           => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'size:2'],
            'gender'                => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'in:M,F'],
            'occupation'            => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'annualSalary'          => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'accountPurpose'        => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'expectedMonthlyVolume' => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idType'                => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'in:PASSPORT,HK_HKID,DLN,GOVERNMENT_ISSUED_ID_CARD'],
            'idNumber'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'min:2', 'max:50'],
            'issueDate'             => ['required_if:cardHolderModel,B2C', 'nullable', 'date_format:Y-m-d'],
            'idNoExpiryDate'        => ['required_if:cardHolderModel,B2C', 'nullable', 'date_format:Y-m-d'],
            'idFrontId'             => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idBackId'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idHoldId'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'ipAddress'             => ['required_if:cardHolderModel,B2C', 'nullable', 'ip'],
            'kycVerification'                => ['nullable', 'array'],
            'kycVerification.provider'       => ['required_with:kycVerification', 'nullable', 'string'],
            'kycVerification.referenceId'    => ['required_with:kycVerification', 'nullable', 'string'],
        ]);

        $result = $this->cardholderService->createCardholderV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/update-v2',
        operationId: 'updateCardholderV2',
        summary: 'Cardholder-Update-v2',
        description: "Update an existing cardholder using the V2 endpoint.\n\n**Note**: Updating is supported only when `status=reject`.\n\n**cardHolderModel values**:\n- `B2B`: Standard model. Common fields only.\n- `B2C`: Extended model. All B2B fields plus nationality, gender, occupation, annualSalary, accountPurpose, expectedMonthlyVolume, idType, idNumber, issueDate, idNoExpiryDate, idFrontId, idBackId, idHoldId, ipAddress. Optional kycVerification object.\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/v2/update",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardHolderModel', 'holderId', 'areaCode', 'mobile', 'email', 'firstName', 'lastName', 'birthday', 'country', 'town', 'address', 'postCode'],
                properties: [
                    new OA\Property(property: 'cardHolderModel',        type: 'string',  example: 'B2C',                description: 'Cardholder business model. Values: B2B, B2C'),
                    new OA\Property(property: 'holderId',               type: 'integer', example: 124024,              description: 'Holder id (from Create Cardholder response)'),
                    new OA\Property(property: 'areaCode',               type: 'string',  example: '1',                 description: 'Mobile phone area code. Length 2–5. Obtain from Support Bins API (supportHolderAreaCode)'),
                    new OA\Property(property: 'mobile',                 type: 'string',  example: '4155550100',        description: 'Mobile phone number. Length 5–20'),
                    new OA\Property(property: 'email',                  type: 'string',  example: 'john@example.com',  description: 'Email. Receives verification code. Length 5–50'),
                    new OA\Property(property: 'firstName',              type: 'string',  example: 'John',              description: 'First name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters'),
                    new OA\Property(property: 'lastName',               type: 'string',  example: 'Doe',               description: 'Last name. Only English characters. Length 2–32. Combined firstName + lastName cannot exceed 32 characters'),
                    new OA\Property(property: 'birthday',               type: 'string',  example: '1990-01-15',        description: 'Date of birth. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'country',                type: 'string',  example: 'US',                description: 'Country/Region code for billing address. ISO 3166-1 alpha-2. Obtain from Support Bins API (supportHolderRegin)'),
                    new OA\Property(property: 'town',                   type: 'string',  example: 'LA',                description: 'City code for billing address. Obtain from City List API (code field)'),
                    new OA\Property(property: 'address',                type: 'string',  example: '123 Main Street',   description: 'Billing address. Length 2–40. Letters, numbers, hyphens and spaces only. Regex: ^[A-Za-z0-9\\- ]+$'),
                    new OA\Property(property: 'postCode',               type: 'string',  example: '90001',             description: 'Postal code. Length 2–15. Regex: ^[a-zA-Z0-9]{1,15}$'),
                    new OA\Property(property: 'nationality',            type: 'string',  example: 'US',                description: '[B2C only] Nationality code. ISO 3166-1 alpha-2. Obtain from Support Bins API (supportHolderNationality)'),
                    new OA\Property(property: 'gender',                 type: 'string',  example: 'M',                 description: '[B2C only] Gender. M: male, F: female'),
                    new OA\Property(property: 'occupation',             type: 'string',  example: '11-1011',           description: '[B2C only] Occupation code. Obtain from Cardholder Occupation API (occupationCode)'),
                    new OA\Property(property: 'annualSalary',           type: 'string',  example: '100000 USD',        description: '[B2C only] Annual salary. Example: 10000000 USD'),
                    new OA\Property(property: 'accountPurpose',         type: 'string',  example: 'Living Expense',    description: '[B2C only] Account purpose. English only'),
                    new OA\Property(property: 'expectedMonthlyVolume',  type: 'string',  example: '10000 USD',         description: '[B2C only] Expected monthly trading volume. Example: 100000 USD'),
                    new OA\Property(property: 'idType',                 type: 'string',  example: 'PASSPORT',          description: '[B2C only] ID type. Values: PASSPORT, HK_HKID (Hong Kong), DLN (Driver\'s license), GOVERNMENT_ISSUED_ID_CARD'),
                    new OA\Property(property: 'idNumber',               type: 'string',  example: 'A12345678',         description: '[B2C only] ID number. Length 2–50'),
                    new OA\Property(property: 'issueDate',              type: 'string',  example: '2020-01-01',        description: '[B2C only] ID document issuance date. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'idNoExpiryDate',         type: 'string',  example: '2030-01-01',        description: '[B2C only] ID document expiration date. Format: yyyy-MM-dd'),
                    new OA\Property(property: 'idFrontId',              type: 'string',  example: 'file_abc123',       description: '[B2C only] Front photo of ID card file ID. Obtain from Upload File API'),
                    new OA\Property(property: 'idBackId',               type: 'string',  example: 'file_def456',       description: '[B2C only] Back photo of ID card file ID. Obtain from Upload File API. If no back view, reuse idFrontId'),
                    new OA\Property(property: 'idHoldId',               type: 'string',  example: 'file_ghi789',       description: '[B2C only] User selfie photo file ID. Obtain from Upload File API'),
                    new OA\Property(property: 'ipAddress',              type: 'string',  example: '192.168.1.1',       description: '[B2C only] Client IPv4 address'),
                    new OA\Property(
                        property: 'kycVerification',
                        type: 'object',
                        description: '[B2C only, optional] KYC verification credentials. Required for card type 111065',
                        properties: [
                            new OA\Property(property: 'provider',    type: 'string', example: 'SUMSUB', description: 'Third-party KYC provider name. Example: SUMSUB'),
                            new OA\Property(property: 'referenceId', type: 'string', example: 'abc123', description: 'Third-party KYC provider referenceId. For SUMSUB, pass the applicantId'),
                        ]
                    ),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'holderId',           type: 'integer', example: 124024,                       description: 'Cardholder id'),
                                new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: '114242059249029235245352442', nullable: true, description: 'Client transaction id'),
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 124024,                       description: 'Card type id'),
                                new OA\Property(property: 'statusFlowLocation', type: 'string',  example: 'admin',                      description: 'Review flow location. Process: Platform review first, then bank review. Update supported only when statusFlowLocation=admin and status=reject. admin: platform review; channel: bank review'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit',                 description: 'Status: wait_audit (Pending), pass_audit (Approved), under_review (In review), reject (Rejected)'),
                                new OA\Property(property: 'description',        type: 'string',  example: 'SUCCESS', nullable: true,    description: 'Description'),
                                new OA\Property(property: 'respMsg',            type: 'string',  example: 'SUCCESS', nullable: true,    description: 'Remark. Deprecated, will be removed'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateCardholderV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardHolderModel'       => ['required', 'string', 'in:B2B,B2C'],
            'holderId'              => ['required', 'integer'],
            'areaCode'              => ['required', 'string', 'min:2', 'max:5'],
            'mobile'                => ['required', 'string', 'min:5', 'max:20'],
            'email'                 => ['required', 'string', 'email', 'max:50'],
            'firstName'             => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'lastName'              => ['required', 'string', 'min:2', 'max:32', 'regex:/^[A-Za-z\s]+$/'],
            'birthday'              => ['required', 'date_format:Y-m-d'],
            'country'               => ['required', 'string', 'size:2'],
            'town'                  => ['required', 'string'],
            'address'               => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'postCode'              => ['required', 'string', 'min:2', 'max:15', 'regex:/^[a-zA-Z0-9]{1,15}$/'],
            // B2C-only fields
            'nationality'           => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'size:2'],
            'gender'                => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'in:M,F'],
            'occupation'            => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'annualSalary'          => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'accountPurpose'        => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'expectedMonthlyVolume' => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idType'                => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'in:PASSPORT,HK_HKID,DLN,GOVERNMENT_ISSUED_ID_CARD'],
            'idNumber'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string', 'min:2', 'max:50'],
            'issueDate'             => ['required_if:cardHolderModel,B2C', 'nullable', 'date_format:Y-m-d'],
            'idNoExpiryDate'        => ['required_if:cardHolderModel,B2C', 'nullable', 'date_format:Y-m-d'],
            'idFrontId'             => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idBackId'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'idHoldId'              => ['required_if:cardHolderModel,B2C', 'nullable', 'string'],
            'ipAddress'             => ['required_if:cardHolderModel,B2C', 'nullable', 'ip'],
            'kycVerification'             => ['nullable', 'array'],
            'kycVerification.provider'    => ['required_with:kycVerification', 'nullable', 'string'],
            'kycVerification.referenceId' => ['required_with:kycVerification', 'nullable', 'string'],
        ]);

        $result = $this->cardholderService->updateCardholderV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/list',
        operationId: 'cardholderList',
        summary: 'Cardholder-List',
        description: "Returns a paginated list of cardholders.\n\n**Note**: `areaCode` and `mobile` must be passed together or not at all.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/query",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',         type: 'integer', example: 1,                          description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',        type: 'integer', example: 10,                         description: 'Number of records per page. Maximum 100, default 10'),
                    new OA\Property(property: 'holderId',        type: 'integer', example: 102424,                     description: 'Holder id (optional filter)'),
                    new OA\Property(property: 'areaCode',        type: 'string',  example: '+1',                       description: 'Mobile area code (optional filter). Must be passed together with mobile. Example: +1'),
                    new OA\Property(property: 'mobile',          type: 'string',  example: '4155550100',               description: 'Mobile phone number (optional filter). Must be passed together with areaCode'),
                    new OA\Property(property: 'email',           type: 'string',  example: 'john@example.com',         description: 'Email (optional filter)'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'ORDER202501010000000001', description: 'Client transaction id (optional filter)'),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 11, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'holderId',           type: 'integer', example: 102424,                                description: 'Cardholder id'),
                                            new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: '24353647ksglsan3535', nullable: true,  description: 'Client transaction id'),
                                            new OA\Property(property: 'cardTypeId',         type: 'integer', example: 102424,                                description: 'Card type id'),
                                            new OA\Property(property: 'areaCode',           type: 'string',  example: '+852',                                description: 'Mobile area code'),
                                            new OA\Property(property: 'mobile',             type: 'string',  example: '875692311',                            description: 'Mobile number'),
                                            new OA\Property(property: 'email',              type: 'string',  example: 'test@test.com',                        description: 'Email'),
                                            new OA\Property(property: 'firstName',          type: 'string',  example: 'elly',                                description: 'First name'),
                                            new OA\Property(property: 'lastName',           type: 'string',  example: 'tom',                                 description: 'Last name'),
                                            new OA\Property(property: 'birthday',           type: 'string',  example: '1990-10-10',                           description: 'Date of birth. YYYY-MM-dd'),
                                            new OA\Property(property: 'country',            type: 'string',  example: 'HK',                                  description: 'Country/Region Code. ISO 3166-1 alpha-2'),
                                            new OA\Property(property: 'countryStr',         type: 'string',  example: 'Hong Kong',                            description: 'Country/Region name'),
                                            new OA\Property(property: 'town',               type: 'string',  example: 'HK_KKC_1',                            description: 'City code'),
                                            new OA\Property(property: 'townStr',            type: 'string',  example: '\u4e5d\u9f99\u57ce\u533a',                             description: 'City name'),
                                            new OA\Property(property: 'address',            type: 'string',  example: 'To Kwa Wan, Kowloon, Hong Kong',       description: 'Billing address'),
                                            new OA\Property(property: 'postCode',           type: 'string',  example: '999077',                              description: 'Postal code'),
                                            new OA\Property(property: 'statusFlowLocation', type: 'string',  example: 'admin',                               description: 'Review flow location. Update supported only when statusFlowLocation=admin and status=reject. admin: Wasabi review; channel: Bank review'),
                                            new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit',                          description: 'Status: wait_audit (Pending), pass_audit (Approved), under_review (In review), reject (Rejected)'),
                                            new OA\Property(property: 'description',        type: 'string',  example: 'SUCCESS',                             description: 'Description'),
                                            new OA\Property(property: 'respMsg',            type: 'string',  example: null, nullable: true,                  description: 'Remark. Deprecated, will be removed'),
                                            new OA\Property(property: 'createTime',         type: 'integer', example: 232413142131,                          description: 'Creation time (Unix milliseconds)'),
                                            new OA\Property(property: 'updateTime',         type: 'integer', example: 232413142131,                          description: 'Last update time (Unix milliseconds)'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cardholderList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'         => ['required', 'integer', 'min:1'],
            'pageSize'        => ['required', 'integer', 'min:1', 'max:100'],
            'holderId'        => ['nullable', 'integer'],
            'areaCode'        => ['nullable', 'string', 'required_with:mobile'],
            'mobile'          => ['nullable', 'string', 'required_with:areaCode'],
            'email'           => ['nullable', 'string', 'email'],
            'merchantOrderNo' => ['nullable', 'string'],
        ]);

        $result = $this->cardholderService->cardholderList($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cardholders/update-email',
        operationId: 'updateCardholderEmail',
        summary: 'Cardholder-Update Email',
        description: "Update the email address of a cardholder.\n\n**Note**: Updating is supported only when cardholder `status=pass_audit`.\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/holder/updateEmail",
        security: [['ApiKeyAuth' => []]],
        tags: ['Cardholder'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['holderId', 'merchantOrderNo', 'email'],
                properties: [
                    new OA\Property(property: 'holderId',        type: 'integer', example: 102424,                     description: 'Holder id'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: '24253524245242542524',     description: 'Client transaction id'),
                    new OA\Property(property: 'email',           type: 'string',  example: 'testB@example.com',        description: 'New email address. Length 5–50'),
                ],
                type: 'object'
            )
        ),
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'holderId',        type: 'integer', example: 102424,                                                    description: 'Cardholder id'),
                                new OA\Property(property: 'merchantOrderNo', type: 'string',  example: '24253524245242542524',                                    description: 'Client transaction id'),
                                new OA\Property(property: 'orderNo',         type: 'string',  example: '202603302038633380833681408',                             description: 'Transaction id'),
                                new OA\Property(property: 'status',          type: 'string',  example: 'pass_audit',                                             description: 'Status: wait_audit (Pending), pass_audit (Approved), under_review (In review), reject (Rejected)'),
                                new OA\Property(property: 'description',     type: 'string',  example: 'update email from [testA@example.com] to [testB@example.com]; ', nullable: true, description: 'Description'),
                                new OA\Property(property: 'remark',          type: 'string',  example: null, nullable: true,                                    description: 'Remark'),
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid API key', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',           content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateCardholderEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holderId'        => ['required', 'integer'],
            'merchantOrderNo' => ['required', 'string'],
            'email'           => ['required', 'string', 'email', 'max:50'],
        ]);

        $result = $this->cardholderService->updateCardholderEmail($validated);

        return $this->success($result);
    }
}
