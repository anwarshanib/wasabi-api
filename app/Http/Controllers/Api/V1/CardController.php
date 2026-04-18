<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\CardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card CARD endpoints to third-party clients.
 */
final class CardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CardService $cardService,
    ) {}

    #[OA\Post(
        path: '/api/v1/cards/support-bins',
        operationId: 'supportBins',
        summary: 'Support Bins',
        description: 'Returns all supported card types (bins) available to the merchant. Use `cardTypeId` from this response when creating a card. Source: Wasabi Card /merchant/core/mcb/card/v2/cardTypes',
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'SUCCESS'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'cardTypeId',                          type: 'integer', example: 111002,        description: 'Card type id'),
                                    new OA\Property(property: 'organization',                        type: 'string',  example: 'Visa',         description: 'Card organization: Visa, MasterCard, Discover'),
                                    new OA\Property(property: 'country',                             type: 'string',  example: 'US',           description: 'Issue country'),
                                    new OA\Property(property: 'bankCardBin',                         type: 'string',  example: '531993',       description: 'Card Bin'),
                                    new OA\Property(property: 'type',                                type: 'string',  example: 'Virtual',      description: 'Card type: Virtual, Physical'),
                                    new OA\Property(property: 'category',                            type: 'string',  example: 'SUBSCRIPTION', description: 'Card category: GIFT, PURCHASE, SUBSCRIPTION, PHYSICAL'),
                                    new OA\Property(property: 'cardName',                            type: 'string',  example: '531993',       description: 'Card name'),
                                    new OA\Property(property: 'cardDesc',                            type: 'string',  example: 'Adobe, Aliexpress, Amazon.', description: 'Card desc'),
                                    new OA\Property(property: 'cardPrice',                           type: 'string',  example: '10',           description: 'Card fee (BigDecimal)'),
                                    new OA\Property(property: 'cardPriceCurrency',                   type: 'string',  example: 'USD',          description: 'Card fee currency'),
                                    new OA\Property(property: 'support',                             type: 'array',   items: new OA\Items(type: 'string'), description: 'Supporting merchant list. For reference only'),
                                    new OA\Property(property: 'risk',                                type: 'array',   items: new OA\Items(type: 'string'), description: 'High risk merchant list. Consumption in this scenario will trigger card cancellation risk'),
                                    new OA\Property(property: 'supportHolderNationality',            type: 'array',   items: new OA\Items(type: 'string'), description: 'Supported cardholder nationalities (ISO 3166-1 alpha-2)'),
                                    new OA\Property(property: 'supportHolderRegin',                  type: 'array',   items: new OA\Items(type: 'string'), description: 'Supported cardholder regions'),
                                    new OA\Property(property: 'supportHolderAreaCode',               type: 'array',   items: new OA\Items(type: 'string'), description: 'Supported cardholder phone area codes'),
                                    new OA\Property(property: 'needCardHolder',                      type: 'boolean', example: false,          description: 'Whether a cardholder is required to create this card'),
                                    new OA\Property(property: 'needDepositForActiveCard',            type: 'boolean', example: true,           description: 'Whether an initial deposit is required to activate the card'),
                                    new OA\Property(property: 'depositAmountMinQuotaForActiveCard',  type: 'string',  example: '10',           description: 'Minimum deposit amount required for card activation (BigDecimal)'),
                                    new OA\Property(property: 'depositAmountMaxQuotaForActiveCard',  type: 'string',  example: '100000',       description: 'Maximum deposit amount allowed for card activation (BigDecimal)'),
                                    new OA\Property(property: 'fiatCurrency',                        type: 'string',  example: 'USD',          description: 'Fiat currency of the card'),
                                    new OA\Property(property: 'balanceRetentionQuota',               type: 'number',  example: 5,              description: 'Minimum balance to retain on the card'),
                                    new OA\Property(property: 'status',                              type: 'string',  example: 'online',       description: 'Card type status'),
                                    new OA\Property(property: 'rechargeCurrency',                    type: 'string',  example: 'USD',          description: 'Recharge currency'),
                                    new OA\Property(property: 'rechargeMinQuota',                    type: 'number',  example: 20,             description: 'Minimum recharge amount'),
                                    new OA\Property(property: 'rechargeMaxQuota',                    type: 'number',  example: 100000,         description: 'Maximum recharge amount'),
                                    new OA\Property(property: 'rechargeFeeRate',                     type: 'number',  example: 1,              description: 'Recharge fee rate (percentage)'),
                                    new OA\Property(property: 'rechargeFixedFee',                    type: 'number',  example: 0,              description: 'Recharge fixed fee'),
                                    new OA\Property(property: 'rechargeDigital',                     type: 'integer', example: 2,              description: 'Decimal places for recharge amounts'),
                                    new OA\Property(property: 'enableActiveCard',                    type: 'boolean', example: true,           description: 'Whether card activation is enabled'),
                                    new OA\Property(property: 'enableDeposit',                       type: 'boolean', example: true,           description: 'Whether deposit is enabled'),
                                    new OA\Property(property: 'enableFreeze',                        type: 'boolean', example: true,           description: 'Whether card freeze is enabled'),
                                    new OA\Property(property: 'enableUnFreeze',                      type: 'boolean', example: true,           description: 'Whether card unfreeze is enabled'),
                                    new OA\Property(
                                        property: 'metadata',
                                        type: 'object',
                                        description: 'Additional card type configuration',
                                        properties: [
                                            new OA\Property(property: 'cardHolderMaxCardLimit',          type: 'integer', example: 500,   description: 'Maximum number of cards per cardholder'),
                                            new OA\Property(property: 'cardHolderModel',                 type: 'string',  example: 'B2B', description: 'Cardholder model'),
                                            new OA\Property(property: 'supportSettingNoPinPaymentAmount',type: 'boolean', example: false, description: 'Whether setting a no-PIN payment amount is supported'),
                                            new OA\Property(property: 'defaultNoPinPaymentAmount',       type: 'number',  example: 500,   description: 'Default no-PIN payment amount'),
                                            new OA\Property(property: 'noPinPaymentAmountMinQuota',      type: 'number',  example: 0,     description: 'Minimum no-PIN payment amount'),
                                            new OA\Property(property: 'noPinPaymentAmountMaxQuota',      type: 'number',  example: 2000,  description: 'Maximum no-PIN payment amount'),
                                            new OA\Property(
                                                property: 'spendingControls',
                                                type: 'array',
                                                description: 'Spending control rules',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'interval',        type: 'string',  example: 'PER_TRANSACTION', description: 'Control interval'),
                                                        new OA\Property(property: 'amount',          type: 'string',  example: '20000',           description: 'Limit amount (BigDecimal)'),
                                                        new OA\Property(property: 'supportSetting',  type: 'boolean', example: true,              description: 'Whether this control can be customised'),
                                                    ],
                                                    type: 'object'
                                                )
                                            ),
                                            new OA\Property(property: 'supportSettingMcc',              type: 'boolean', example: true,  description: 'Whether MCC (merchant category code) filtering is supported'),
                                            new OA\Property(property: 'supportUpdateCardHolerEmail',    type: 'boolean', example: true,  description: 'Whether updating the cardholder email is supported'),
                                        ]
                                    ),
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
    public function supportBins(): JsonResponse
    {
        $result = $this->cardService->supportBins();

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/create',
        operationId: 'createCardDeprecated',
        summary: 'Create Card (Deprecated)',
        description: "**Deprecated** — Create (open) a new card.\n\n`cardNo` is only returned in the response when `status=success`.\n\n`amount`: The deposit amount when creating the card. If not passed, defaults to `depositAmountMinQuotaForActiveCard`. Valid range from the Support Bins response: `needDepositForActiveCard=true` AND `depositAmountMinQuotaForActiveCard <= amount <= depositAmountMaxQuotaForActiveCard`.\n\n`cardNumber`: Required when creating a physical card.\n\n`accountId`: All payments are deducted from this account. Obtain from the Account List API.\n\nSource: Wasabi Card /merchant/core/mcb/card/openCard",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        deprecated: true,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantOrderNo', 'cardTypeId'],
                properties: [
                    new OA\Property(
                        property: 'merchantOrderNo',
                        type: 'string',
                        example: 'ORDER20250101000000001',
                        description: 'Client transaction id. Length must be between 15 and 65 characters'
                    ),
                    new OA\Property(
                        property: 'cardTypeId',
                        type: 'integer',
                        example: 1,
                        description: 'Card type id (from Support Bins API)'
                    ),
                    new OA\Property(
                        property: 'holderId',
                        type: 'integer',
                        example: 1979009257233215490,
                        description: 'Cardholder id (optional)'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'string',
                        example: '20.00',
                        description: 'Deposit amount when creating the card (BigDecimal as string). Optional — defaults to depositAmountMinQuotaForActiveCard if not passed'
                    ),
                    new OA\Property(
                        property: 'cardNumber',
                        type: 'string',
                        example: '5318930000000001',
                        description: 'Card number. Required when creating a physical card'
                    ),
                    new OA\Property(
                        property: 'accountId',
                        type: 'integer',
                        example: 1979009257233215490,
                        description: 'Account ID. All payments will be deducted from this account. Obtain from the Account List API'
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
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'orderNo',          type: 'string',   example: '1852379830190366720',  description: 'Transaction id'),
                                    new OA\Property(property: 'merchantOrderNo',  type: 'string',   example: 'T1852379826671345664', description: 'Client transaction id'),
                                    new OA\Property(property: 'cardNo',           type: 'string',   example: null,                  nullable: true, description: 'Card id — only returned when status=success'),
                                    new OA\Property(property: 'currency',         type: 'string',   example: 'USD',                 description: 'Currency'),
                                    new OA\Property(property: 'amount',           type: 'string',   example: '15',                  description: 'Amount (BigDecimal)'),
                                    new OA\Property(property: 'fee',              type: 'string',   example: '0',                   description: 'Fee (BigDecimal)'),
                                    new OA\Property(property: 'receivedAmount',   type: 'string',   example: '0',                   description: 'Received amount after fee deduction (BigDecimal)'),
                                    new OA\Property(property: 'receivedCurrency', type: 'string',   example: 'USD',                 description: 'Received currency'),
                                    new OA\Property(property: 'type',             type: 'string',   example: 'create',              description: 'Transaction type'),
                                    new OA\Property(property: 'status',           type: 'string',   example: 'processing',          description: 'Order status'),
                                    new OA\Property(property: 'remark',           type: 'string',   example: null,                  nullable: true, description: 'Remark'),
                                    new OA\Property(property: 'transactionTime',  type: 'integer',  example: 1730476741729,         description: 'Transaction time (Unix ms)'),
                                ],
                                type: 'object'
                            )
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
    public function createCardDeprecated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'cardTypeId'      => ['required', 'integer'],
            'holderId'        => ['nullable', 'integer'],
            'amount'          => ['nullable', 'numeric', 'gt:0'],
            'cardNumber'      => ['nullable', 'string', 'max:19'],
            'accountId'       => ['nullable', 'integer'],
        ]);

        if (isset($validated['amount'])) {
            $validated['amount'] = (string) $validated['amount'];
        }

        $result = $this->cardService->createCardDeprecated($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/create-v2',
        operationId: 'createCardV2',
        summary: 'Create Card V2',
        description: "Create (open) a new card using the V2 endpoint.\n\n`amount` is **required** in V2 (unlike the deprecated endpoint where it was optional).\n\n`designId`: Optional brand design ID for white-label and gift card customisation — obtain available IDs from your Wasabi account manager.\n\n`cardNumber`: Required when creating a physical card.\n\n`accountId`: All payments are deducted from this account. Obtain from the Account List API.\n\nReturns a single order object. `cardNo` is only populated once `status=success`.\n\nSource: Wasabi Card /merchant/core/mcb/card/v2/createCard",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantOrderNo', 'cardTypeId', 'amount'],
                properties: [
                    new OA\Property(
                        property: 'merchantOrderNo',
                        type: 'string',
                        example: 'ORDER20250101000000001',
                        description: 'Client transaction id. Length must be between 15 and 65 characters'
                    ),
                    new OA\Property(
                        property: 'cardTypeId',
                        type: 'integer',
                        example: 111002,
                        description: 'Card type id (from Support Bins API)'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'string',
                        example: '20.00',
                        description: 'Deposit amount when creating the card (BigDecimal as string). Required in V2'
                    ),
                    new OA\Property(
                        property: 'holderId',
                        type: 'integer',
                        example: 1979009257233215490,
                        description: 'Cardholder id (optional)'
                    ),
                    new OA\Property(
                        property: 'cardNumber',
                        type: 'string',
                        example: '5318930000000001',
                        description: 'Card number. Required when creating a physical card'
                    ),
                    new OA\Property(
                        property: 'accountId',
                        type: 'integer',
                        example: 1979009257233215490,
                        description: 'Account ID. All payments will be deducted from this account. Obtain from the Account List API'
                    ),
                    new OA\Property(
                        property: 'designId',
                        type: 'string',
                        example: 'design_abc123',
                        description: 'Brand design ID for white-label and gift card customisation (optional)'
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
                                new OA\Property(property: 'orderNo',          type: 'string',   example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',   example: 'ORDER20250101000000001', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',   example: 'CARD0000001',          description: 'Card id — only returned when status=success'),
                                new OA\Property(property: 'currency',         type: 'string',   example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',   example: 20,                    description: 'Amount'),
                                new OA\Property(property: 'fee',              type: 'number',   example: 0,                     description: 'Fee'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',   example: 20,                    description: 'Received amount after fee deduction'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',   example: 'USD',                 description: 'Received currency'),
                                new OA\Property(property: 'type',             type: 'string',   example: 'create',              description: 'Transaction type'),
                                new OA\Property(property: 'status',           type: 'string',   example: 'wait_process',        description: 'Order status'),
                                new OA\Property(property: 'description',      type: 'string',   example: null, nullable: true,  description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',   example: null, nullable: true,  description: 'Remark'),
                                new OA\Property(property: 'transactionTime',  type: 'integer',  example: 1730476741729,         description: 'Transaction time (Unix ms)'),
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
    public function createCardV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'cardTypeId'      => ['required', 'integer'],
            'amount'          => ['required', 'numeric', 'gt:0'],
            'holderId'        => ['nullable', 'integer'],
            'cardNumber'      => ['nullable', 'string', 'max:19'],
            'accountId'       => ['nullable', 'integer'],
            'designId'        => ['nullable', 'string', 'max:64'],
        ]);

        $validated['amount'] = (string) $validated['amount'];

        $result = $this->cardService->createCardV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/info',
        operationId: 'cardInfo',
        summary: 'Card Info',
        description: "Retrieve detailed information for a specific card.\n\nWhen `onlySimpleInfo=true` (default), the `balanceInfo` field is excluded — use this for faster responses when balance data is not needed.\n\nSet `onlySimpleInfo=false` to include the card's current balance in the response.\n\nSource: Wasabi Card /merchant/core/mcb/card/info",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo'],
                properties: [
                    new OA\Property(
                        property: 'cardNo',
                        type: 'string',
                        example: 'CARD0000001',
                        description: 'Card id'
                    ),
                    new OA\Property(
                        property: 'onlySimpleInfo',
                        type: 'boolean',
                        example: true,
                        description: 'When true (default), excludes balance info from the response'
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
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 111002,        description: 'Card type id'),
                                new OA\Property(property: 'cardBankBin',        type: 'string',  example: '531993',       description: 'Card bank bin'),
                                new OA\Property(property: 'holderId',           type: 'integer', example: null, nullable: true, description: 'Cardholder id'),
                                new OA\Property(property: 'cardNo',             type: 'string',  example: 'CARD0000001',  description: 'Card id'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'normal',       description: 'Card status: pending, un_activated, normal, cancel, freeze, blocked'),
                                new OA\Property(property: 'blocked',            type: 'boolean', example: false,          description: 'Whether the card is blocked'),
                                new OA\Property(property: 'bindTime',           type: 'integer', example: 1730476741729,  description: 'Card bind time (Unix ms)'),
                                new OA\Property(property: 'remark',             type: 'string',  example: null, nullable: true, description: 'Remark'),
                                new OA\Property(property: 'noPinPaymentAmount', type: 'number',  example: 500,            description: 'No-PIN payment amount limit'),
                                new OA\Property(
                                    property: 'balanceInfo',
                                    type: 'object',
                                    description: 'Balance info — only populated when onlySimpleInfo=false',
                                    properties: [
                                        new OA\Property(property: 'cardNo',      type: 'string', example: 'CARD0000001', description: 'Card id'),
                                        new OA\Property(property: 'amount',      type: 'number', example: 100,           description: 'Total balance'),
                                        new OA\Property(property: 'usedAmount',  type: 'number', example: 20,            description: 'Used balance'),
                                        new OA\Property(property: 'currency',    type: 'string', example: 'USD',         description: 'Currency'),
                                    ]
                                ),
                                new OA\Property(property: 'customCategory',     type: 'string',  example: 'SUBSCRIPTION', description: 'Custom card category'),
                                new OA\Property(
                                    property: 'holderInfo',
                                    type: 'object',
                                    description: 'Cardholder personal information',
                                    properties: [
                                        new OA\Property(property: 'firstName',    type: 'string', example: 'John',      description: 'First name'),
                                        new OA\Property(property: 'lastName',     type: 'string', example: 'Doe',       description: 'Last name'),
                                        new OA\Property(property: 'country',      type: 'string', example: 'US',        description: 'Country (ISO 3166-1 alpha-2)'),
                                        new OA\Property(property: 'state',        type: 'string', example: 'CA',        description: 'State'),
                                        new OA\Property(property: 'town',         type: 'string', example: 'Los Angeles', description: 'Town / city'),
                                        new OA\Property(property: 'address',      type: 'string', example: '123 Main St', description: 'Address line 1'),
                                        new OA\Property(property: 'addressLine2', type: 'string', example: 'Apt 4B',   description: 'Address line 2'),
                                        new OA\Property(property: 'postCode',     type: 'string', example: '90001',     description: 'Post code'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'spendingControls',
                                    type: 'array',
                                    description: 'Spending control rules',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'interval',       type: 'string',  example: 'PER_TRANSACTION', description: 'Control interval'),
                                            new OA\Property(property: 'amount',         type: 'number',  example: 20000,             description: 'Limit amount'),
                                            new OA\Property(property: 'supportSetting', type: 'boolean', example: true,              description: 'Whether this control can be customised'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'riskControls',
                                    type: 'object',
                                    description: 'Risk control rules',
                                    properties: [
                                        new OA\Property(
                                            property: 'allowedMcc',
                                            type: 'array',
                                            description: 'Allowed MCC (merchant category codes)',
                                            items: new OA\Items(type: 'string', example: '5411')
                                        ),
                                    ]
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
    public function cardInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'         => ['required', 'string'],
            'onlySimpleInfo' => ['nullable', 'boolean'],
        ]);

        $result = $this->cardService->cardInfo($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/sensitive',
        operationId: 'cardInfoForSensitive',
        summary: 'Card Info For Sensitive',
        description: "Retrieve sensitive card data: card number, CVV, expiry date, and activate URL.\n\nAll response values are **RSA-encrypted** with the user's public key and must be decrypted using the merchant's private key.\n\n**Gift cards**: `cardNumber`, `cvv`, and `expireDate` are NOT returned. `activateUrl` IS returned.\n\n**Non-gift cards**: `cardNumber`, `cvv`, and `expireDate` are returned. `activateUrl` is NOT returned.\n\nSource: Wasabi Card /merchant/core/mcb/card/sensitive",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo'],
                properties: [
                    new OA\Property(
                        property: 'cardNo',
                        type: 'string',
                        example: 'CARD0000001',
                        description: 'Card id'
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
                                new OA\Property(property: 'cardNumber',  type: 'string', example: 'bQoXms9ScNxy37b1Jgy...', description: 'RSA-encrypted card number. Gift card not returned'),
                                new OA\Property(property: 'cvv',         type: 'string', example: 'bQoXms9ScNxy37b1Jgy...', description: 'RSA-encrypted CVV. Gift card not returned'),
                                new OA\Property(property: 'expireDate',  type: 'string', example: 'gQldrvKSV3cWXuCbrUgt...', description: 'RSA-encrypted expiry date (MM/YYYY). Gift card not returned'),
                                new OA\Property(property: 'activateUrl', type: 'string', example: 'gQldrvKSV3cWXuCbrUgt...', nullable: true, description: 'RSA-encrypted activation URL. Gift card only'),
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
    public function cardInfoForSensitive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo' => ['required', 'string'],
        ]);

        $result = $this->cardService->cardInfoForSensitive($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/balance',
        operationId: 'cardBalance',
        summary: 'Card Balance',
        description: "Retrieve the available balance for a specific card.\n\nNote: some card bins do not support `usedAmount` — it may be returned as 0 or null for those cards.\n\nSource: Wasabi Card /merchant/core/mcb/card/balanceInfo",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo'],
                properties: [
                    new OA\Property(
                        property: 'cardNo',
                        type: 'string',
                        example: 'FC202408181555232422322004',
                        description: 'Card id'
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
                                new OA\Property(property: 'cardNo',      type: 'string', example: 'FC202408181555232422322004', description: 'Card id'),
                                new OA\Property(property: 'amount',      type: 'number', example: 10,                          description: 'Available balance (BigDecimal)'),
                                new OA\Property(property: 'usedAmount',  type: 'number', example: 1,                           description: 'Amount used. Some card bins do not support this field (BigDecimal)'),
                                new OA\Property(property: 'currency',    type: 'string', example: 'USD',                       description: 'Currency'),
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
    public function cardBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo' => ['required', 'string'],
        ]);

        $result = $this->cardService->cardBalance($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/list',
        operationId: 'cardList',
        summary: 'Card List',
        description: "Return a paginated list of cards for the merchant.\n\n`pageNum` defaults to 1. `pageSize` defaults to 10, maximum 100.\n\nAll filter params are optional: filter by `cardNo`, `status`, `cardTypeId`, `holderId`, or creation time range (`startTime`/`endTime` as millisecond Unix timestamps).\n\n**Status values**: `pending`, `un_activated`, `Normal`, `Freeze`, `Freezing`, `UnFreezing`, `canceling`, `cancel`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/list",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',    type: 'integer', example: 1,       description: 'Current page number. Default 1'),
                    new OA\Property(property: 'pageSize',   type: 'integer', example: 10,      description: 'Records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'cardNo',     type: 'string',  example: 'WB202602102021097685055463424', description: 'Filter by card id (optional)'),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'Normal',
                        description: 'Filter by card status (optional). Values: pending, un_activated, Normal, Freeze, Freezing, UnFreezing, canceling, cancel, fail'
                    ),
                    new OA\Property(property: 'cardTypeId', type: 'integer', example: 111039,  description: 'Filter by card type id (optional)'),
                    new OA\Property(property: 'holderId',   type: 'integer', example: 62119,   description: 'Filter by cardholder id (optional)'),
                    new OA\Property(property: 'startTime',  type: 'integer', example: 1770702301000, description: 'Filter by card creation time — range start (Unix ms, optional)'),
                    new OA\Property(property: 'endTime',    type: 'integer', example: 1770789600000, description: 'Filter by card creation time — range end (Unix ms, optional)'),
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
                        new OA\Property(property: 'msg',     type: 'string',  example: 'SUCCESS'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 45, description: 'Total records matching the filter'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'cardTypeId',         type: 'integer', example: 111039,                        description: 'Card type id'),
                                            new OA\Property(property: 'cardBankBin',        type: 'string',  example: '493724',                       description: 'Bank bin'),
                                            new OA\Property(property: 'holderId',           type: 'integer', example: 62119,          nullable: true,  description: 'Cardholder id'),
                                            new OA\Property(property: 'cardNo',             type: 'string',  example: 'WB202602102021097685055463424', description: 'Card id'),
                                            new OA\Property(
                                                property: 'status',
                                                type: 'string',
                                                example: 'Normal',
                                                description: 'Card status: pending, un_activated, Normal, Freeze, Freezing, UnFreezing, canceling, cancel, fail'
                                            ),
                                            new OA\Property(property: 'blocked',            type: 'boolean', example: false,                          description: 'Whether the card is blocked'),
                                            new OA\Property(property: 'bindTime',           type: 'integer', example: 1770702301000,                  description: 'Card creation time (Unix ms)'),
                                            new OA\Property(property: 'remark',             type: 'string',  example: null,           nullable: true,  description: 'Remark'),
                                            new OA\Property(property: 'noPinPaymentAmount', type: 'string',  example: '0',                            description: 'Physical card password-free payment amount limit (BigDecimal)'),
                                            new OA\Property(
                                                property: 'balanceInfo',
                                                type: 'object',
                                                nullable: true,
                                                description: 'Card balance information',
                                                properties: [
                                                    new OA\Property(property: 'cardNo',      type: 'string', example: 'WB202602102021097685055463424', description: 'Card id'),
                                                    new OA\Property(property: 'amount',      type: 'string', example: '23',  description: 'Available balance (BigDecimal)'),
                                                    new OA\Property(property: 'usedAmount',  type: 'string', example: '1',   description: 'Amount used. Some card bins do not support this field (BigDecimal)'),
                                                    new OA\Property(property: 'currency',    type: 'string', example: 'USD', description: 'Currency'),
                                                ]
                                            ),
                                            new OA\Property(property: 'customCategory',     type: 'string',  example: 'SUBSCRIPTION',                 description: 'Card category / purpose'),
                                            new OA\Property(
                                                property: 'holderInfo',
                                                type: 'object',
                                                nullable: true,
                                                description: 'Cardholder information',
                                                properties: [
                                                    new OA\Property(property: 'firstName',    type: 'string', example: 'John',                description: 'Name'),
                                                    new OA\Property(property: 'lastName',     type: 'string', example: 'Zhang',               description: 'Surname'),
                                                    new OA\Property(property: 'country',      type: 'string', example: 'The United States',   description: 'Country'),
                                                    new OA\Property(property: 'state',        type: 'string', example: 'Florida',             description: 'State'),
                                                    new OA\Property(property: 'town',         type: 'string', example: 'Panama City Beach',   description: 'Town'),
                                                    new OA\Property(property: 'address',      type: 'string', example: '301 Argonaut Street', description: 'Address'),
                                                    new OA\Property(property: 'addressLine2', type: 'string', example: 'XXXX XX',             description: 'Second row address'),
                                                    new OA\Property(property: 'postCode',     type: 'string', example: '32413',               description: 'Post code'),
                                                ]
                                            ),
                                            new OA\Property(
                                                property: 'spendingControls',
                                                type: 'array',
                                                nullable: true,
                                                description: 'Spending control rules',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'interval', type: 'string', example: 'PER_TRANSACTION', description: 'Spending interval'),
                                                        new OA\Property(property: 'amount',   type: 'string', example: '10',              description: 'Spending amount (BigDecimal)'),
                                                    ],
                                                    type: 'object'
                                                )
                                            ),
                                            new OA\Property(
                                                property: 'riskControls',
                                                type: 'object',
                                                nullable: true,
                                                description: 'Risk control rules',
                                                properties: [
                                                    new OA\Property(
                                                        property: 'allowedMcc',
                                                        type: 'array',
                                                        description: 'Allowed MCC codes',
                                                        items: new OA\Items(type: 'string', example: '7311')
                                                    ),
                                                    new OA\Property(
                                                        property: 'blockedMcc',
                                                        type: 'array',
                                                        description: 'Blocked MCC codes',
                                                        items: new OA\Items(type: 'string', example: '5411')
                                                    ),
                                                ]
                                            ),
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
    public function cardList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'    => ['required', 'integer', 'min:1'],
            'pageSize'   => ['required', 'integer', 'min:1', 'max:100'],
            'cardNo'     => ['nullable', 'string'],
            'status'     => ['nullable', 'string', 'in:pending,un_activated,Normal,Freeze,Freezing,UnFreezing,canceling,cancel,fail'],
            'cardTypeId' => ['nullable', 'integer'],
            'holderId'   => ['nullable', 'integer'],
            'startTime'  => ['nullable', 'integer'],
            'endTime'    => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardList($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/update',
        operationId: 'updateCard',
        summary: 'Update Card',
        description: "Update card attributes.\n\nUpdatable fields:\n- `noPinPaymentAmount`: updatable only when `metadata.supportSettingNoPinPaymentAmount = true` in Support Bins. Valid range: `metadata.noPinPaymentAmountMinQuota <= value <= metadata.noPinPaymentAmountMaxQuota`.\n- `spendingControls`: updatable only when `metadata.spendingControls = true` and `supportSetting = true` for that interval.\n- `riskControls`: updatable only when `metadata.supportSettingMcc = true`. Only one of `allowedMcc` or `blockedMcc` can be set per card. Pass an empty array to remove MCC rules.\n- `clientRemark`: free-text note, max 50 chars.\n\nSource: Wasabi Card /merchant/core/mcb/card/updateAttribute",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo'],
                properties: [
                    new OA\Property(property: 'cardNo',           type: 'string',  example: 'CARD0000001',          description: 'Card id'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'clientRemark',    type: 'string',  example: 'My card note',         description: 'Client remark. Length 0–50 characters (optional)'),
                    new OA\Property(property: 'noPinPaymentAmount', type: 'string', example: '2000',               description: 'Physical card password-free payment amount limit (BigDecimal). Updatable only when metadata.supportSettingNoPinPaymentAmount=true'),
                    new OA\Property(
                        property: 'spendingControls',
                        type: 'array',
                        description: 'Spending control rules. Updatable only when metadata.spendingControls=true and supportSetting=true for the interval',
                        items: new OA\Items(
                            required: ['interval', 'amount'],
                            properties: [
                                new OA\Property(property: 'interval', type: 'string', example: 'PER_TRANSACTION', description: 'Spending interval (e.g. PER_TRANSACTION)'),
                                new OA\Property(property: 'amount',   type: 'string', example: '10000',           description: 'Spending amount limit (BigDecimal)'),
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(
                        property: 'riskControls',
                        type: 'object',
                        description: 'Risk control rules. Updatable only when metadata.supportSettingMcc=true. Only one of allowedMcc or blockedMcc may be set. Pass empty array to remove.',
                        properties: [
                            new OA\Property(property: 'allowedMcc', type: 'array', description: 'Allowed MCC whitelist', items: new OA\Items(type: 'string', example: '7311')),
                            new OA\Property(property: 'blockedMcc', type: 'array', description: 'Blocked MCC blacklist', items: new OA\Items(type: 'string', example: '5411')),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',          description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664',         description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244',         description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                         description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 0,                             description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                             description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                             description: 'Amount received — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',  nullable: true,         description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'card_update',                 description: 'Transaction type. card_update: Update card'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'success',                     description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'description',      type: 'string',  example: 'update pin free amount from [200] to [2000];', nullable: true, description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',  example: 'update pin free amount from [200] to [2000];', nullable: true, description: 'Transaction notes — equal to description, will be removed'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,                description: 'Transaction time (Unix ms)'),
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
    public function updateCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'                       => ['required', 'string'],
            'merchantOrderNo'              => ['required', 'string', 'min:15', 'max:65'],
            'clientRemark'                 => ['nullable', 'string', 'max:50'],
            'noPinPaymentAmount'           => ['nullable', 'numeric', 'min:0'],
            'spendingControls'             => ['nullable', 'array'],
            'spendingControls.*.interval'  => ['required_with:spendingControls', 'string'],
            'spendingControls.*.amount'    => ['required_with:spendingControls', 'numeric', 'min:0'],
            'riskControls'                 => ['nullable', 'array'],
            'riskControls.allowedMcc'      => ['nullable', 'array'],
            'riskControls.allowedMcc.*'    => ['string'],
            'riskControls.blockedMcc'      => ['nullable', 'array'],
            'riskControls.blockedMcc.*'    => ['string'],
        ]);

        if (isset($validated['noPinPaymentAmount'])) {
            $validated['noPinPaymentAmount'] = (string) $validated['noPinPaymentAmount'];
        }

        if (! empty($validated['spendingControls'])) {
            foreach ($validated['spendingControls'] as &$control) {
                $control['amount'] = (string) $control['amount'];
            }
            unset($control);
        }

        $result = $this->cardService->updateCard($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/note',
        operationId: 'updateNote',
        summary: 'Update Note',
        description: "Update the client remark (note) on a card.\n\n`clientRemark`: optional free-text note of 0–50 characters. Omit or pass null to clear the remark.\n\nReturns the full updated card info object.\n\nSource: Wasabi Card /merchant/core/mcb/card/note",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo'],
                properties: [
                    new OA\Property(property: 'cardNo',       type: 'string', example: 'CARD0000001',  description: 'Card id'),
                    new OA\Property(property: 'clientRemark', type: 'string', example: 'My card note', description: 'Client remark. Length 0–50 characters (optional — omit or pass null to clear)'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response — returns the full updated card info object',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'cardTypeId',         type: 'integer', example: 111040,                       description: 'Card type id'),
                                new OA\Property(property: 'cardBankBin',        type: 'string',  example: '493724',                      description: 'Bank bin'),
                                new OA\Property(property: 'holderId',           type: 'integer', example: 10090,         nullable: true, description: 'Cardholder id'),
                                new OA\Property(property: 'cardNo',             type: 'string',  example: 'WA202408181555232422322004',  description: 'Card id'),
                                new OA\Property(property: 'status',             type: 'string',  example: 'cancel',                      description: 'Card status: pending, un_activated, Normal, Freeze, Freezing, UnFreezing, canceling, cancel, fail'),
                                new OA\Property(property: 'blocked',            type: 'boolean', example: false,                         description: 'Whether the card is blocked'),
                                new OA\Property(property: 'bindTime',           type: 'integer', example: 1723997214000,                 description: 'Card creation time (Unix ms)'),
                                new OA\Property(property: 'remark',             type: 'string',  example: null,          nullable: true, description: 'Remark'),
                                new OA\Property(property: 'noPinPaymentAmount', type: 'number',  example: 500,                           description: 'Physical card password-free payment amount limit (BigDecimal)'),
                                new OA\Property(
                                    property: 'balanceInfo',
                                    type: 'object',
                                    nullable: true,
                                    description: 'Card balance information',
                                    properties: [
                                        new OA\Property(property: 'cardNo',     type: 'string', example: 'WA202408181555232422322004', description: 'Card id'),
                                        new OA\Property(property: 'amount',     type: 'number', example: 10,  description: 'Available balance (BigDecimal)'),
                                        new OA\Property(property: 'usedAmount', type: 'number', example: 1,   description: 'Amount used. Some card bins do not support this field (BigDecimal)'),
                                        new OA\Property(property: 'currency',   type: 'string', example: 'USD', description: 'Currency'),
                                    ]
                                ),
                                new OA\Property(property: 'customCategory',     type: 'string',  example: 'SUBSCRIPTION',                description: 'Card category / purpose'),
                                new OA\Property(
                                    property: 'holderInfo',
                                    type: 'object',
                                    nullable: true,
                                    description: 'Cardholder information',
                                    properties: [
                                        new OA\Property(property: 'firstName',    type: 'string', example: 'John',                description: 'Name'),
                                        new OA\Property(property: 'lastName',     type: 'string', example: 'Zhang',               description: 'Surname'),
                                        new OA\Property(property: 'country',      type: 'string', example: 'The United States',   description: 'Country'),
                                        new OA\Property(property: 'state',        type: 'string', example: 'Florida',             description: 'State'),
                                        new OA\Property(property: 'town',         type: 'string', example: 'Panama City Beach',   description: 'Town'),
                                        new OA\Property(property: 'address',      type: 'string', example: '301 Argonaut Street', description: 'Address'),
                                        new OA\Property(property: 'addressLine2', type: 'string', example: 'XXXX XX',             description: 'Second row address'),
                                        new OA\Property(property: 'postCode',     type: 'string', example: '32413',               description: 'Post code'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'spendingControls',
                                    type: 'array',
                                    nullable: true,
                                    description: 'Spending control rules',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'interval',       type: 'string',  example: 'PER_TRANSACTION', description: 'Spending interval'),
                                            new OA\Property(property: 'amount',         type: 'number',  example: 20000,             description: 'Spending amount limit (BigDecimal)'),
                                            new OA\Property(property: 'supportSetting', type: 'boolean', example: true,              description: 'Whether this control can be customised'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'riskControls',
                                    type: 'object',
                                    nullable: true,
                                    description: 'Risk control rules',
                                    properties: [
                                        new OA\Property(property: 'allowedMcc', type: 'array', description: 'Allowed MCC codes', items: new OA\Items(type: 'string', example: '7311')),
                                    ]
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
    public function updateNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'       => ['required', 'string'],
            'clientRemark' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->cardService->updateNote($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/freeze',
        operationId: 'freezeCardV2',
        summary: 'Freeze Card-V2',
        description: "Freeze a card using the V2 endpoint.\n\nOnce frozen, the card cannot be used for transactions until unfrozen.\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/v2/freeze",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string', example: 'CARD0000001',           description: 'Card id'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'clientRemark',    type: 'string', example: 'Freeze requested',      description: 'Client remark. Length 0–50 characters (optional)'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 0,                     description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                     description: 'Amount received — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD', nullable: true,  description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'Freeze',              description: 'Transaction type. Freeze: Freeze'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'processing',          description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'description',      type: 'string',  example: 'Freeze', nullable: true, description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',  example: 'Freeze', nullable: true, description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function freezeCardV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'clientRemark'    => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->cardService->freezeCardV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/unfreeze',
        operationId: 'unfreezeCardV2',
        summary: 'UnFreeze Card-V2',
        description: "Unfreeze a previously frozen card using the V2 endpoint.\n\nOnce unfrozen, the card can resume normal transactions.\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/v2/unfreeze",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string', example: 'CARD0000001',           description: 'Card id'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'clientRemark',    type: 'string', example: 'Unfreeze requested',    description: 'Client remark. Length 0–50 characters (optional)'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 0,                     description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                     description: 'Amount received — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD', nullable: true,  description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'UnFreeze',            description: 'Transaction type. UnFreeze: UnFreeze'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'processing',          description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'description',      type: 'string',  example: 'UnFreeze', nullable: true, description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',  example: 'UnFreeze', nullable: true, description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function unfreezeCardV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'clientRemark'    => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->cardService->unfreezeCardV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/deposit',
        operationId: 'depositCard',
        summary: 'Deposit Card',
        description: "Deposit funds into a card.\n\nThe deposit amount must be ≥ the minimum allowed by the card type's `rechargeMinQuota` and ≤ `rechargeMaxQuota`.\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/deposit",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo', 'amount'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string',  example: '38928421021320391244', description: 'Card ID'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'amount',          type: 'number',  example: 15, description: 'Deposit amount (BigDecimal). Must be ≥ 0.01'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 15,                    description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                     description: 'Amount received — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                 description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'deposit',             description: 'Transaction type. deposit: Deposit'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'processing',          description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'description',      type: 'string',  example: 'Deposit', nullable: true, description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',  example: 'Card Deposit', nullable: true, description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function depositCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'amount'          => ['required', 'numeric', 'gt:0'],
        ]);

        $validated['amount'] = (string) $validated['amount'];

        $result = $this->cardService->depositCard($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/withdraw',
        operationId: 'withdrawCard',
        summary: 'Withdraw Card',
        description: "Withdraw funds from a card back to the merchant account.\n\nThe withdrawal amount must be ≥ 0.01.\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/withdraw",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo', 'amount'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string', example: '38928421021320391244', description: 'Card ID'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'amount',          type: 'number', example: 15, description: 'Withdrawal amount (BigDecimal). Must be ≥ 0.01'),
                    new OA\Property(property: 'clientRemark',    type: 'string', example: 'Withdraw request', description: 'Client remark. Length 0–50 characters (optional)'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 15,                    description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                     description: 'Amount received — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                 description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'withdraw',            description: 'Transaction type. withdraw: Withdraw'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'success',             description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'remark',           type: 'string',  example: 'withdraw', nullable: true, description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function withdrawCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'clientRemark'    => ['nullable', 'string', 'max:50'],
        ]);

        $validated['amount'] = (string) $validated['amount'];

        $result = $this->cardService->withdrawCard($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/cancel',
        operationId: 'cancelCard',
        summary: 'Cancel Card',
        description: "Cancel a card permanently. After cancellation the card cannot be used.\n\nAny remaining balance on the card will be returned to the merchant account (reflected as `receivedAmount` when `status=success`).\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/cancel",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string', example: '38928421021320391244', description: 'Card ID'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'clientRemark',    type: 'string', example: 'Cancel card', description: 'Client remark. Length 0–50 characters (optional)'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 15,                    description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 15,                    description: 'Amount received (remaining card balance returned to merchant) — only populated when status=success (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                 description: 'Account currency — only populated when status=success'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'cancel',              description: 'Transaction type. cancel: Cancel Card'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'success',             description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'remark',           type: 'string',  example: null, nullable: true,  description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function cancelCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'clientRemark'    => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->cardService->cancelCard($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/activate-physical',
        operationId: 'activatePhysicalCard',
        summary: 'Activate Card (physical)',
        description: "Activate a physical card by providing the card PIN and activation code.\n\n`pin`: 6-digit card password set by the user.\n\n`activeCode`: Activation code printed on or provided with the physical card.\n\n`noPinPaymentAmount`: The contactless/no-PIN payment limit (0–2000 USD). Defaults to 500 USD if not provided.\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/physicalCard/activeCard",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantOrderNo', 'cardNo', 'pin', 'activeCode'],
                properties: [
                    new OA\Property(property: 'merchantOrderNo',    type: 'string',  example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'cardNo',            type: 'string',  example: '38928421021320391244',   description: 'Card id'),
                    new OA\Property(property: 'pin',               type: 'string',  example: '123456',                description: 'Card password. 6 digits. Rules: no 3+ consecutive repeated digits; not a fully ascending or descending sequence; no repeated two- or three-digit segments (e.g., 123123, 909090, 121212)'),
                    new OA\Property(property: 'activeCode',        type: 'string',  example: 'ACT123456',             description: 'Activation code provided with the physical card'),
                    new OA\Property(property: 'noPinPaymentAmount', type: 'number', example: 500,                     description: 'Contactless / no-PIN payment limit (BigDecimal). Range: 0–2000 USD. Defaults to 500 USD (optional)'),
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
                                new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',          type: 'string', example: '38928421021320391244',   description: 'Card id'),
                                new OA\Property(property: 'type',            type: 'string', example: 'card_activated',         description: 'Transaction type. card_activated: Activate Card'),
                                new OA\Property(property: 'status',          type: 'string', example: 'success',                description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'remark',          type: 'string', example: null, nullable: true,     description: 'Transaction notes'),
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
    public function activatePhysicalCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo'    => ['required', 'string', 'min:15', 'max:65'],
            'cardNo'             => ['required', 'string'],
            'pin'                => ['required', 'string', 'digits:6'],
            'activeCode'         => ['required', 'string'],
            'noPinPaymentAmount' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        ]);

        if (isset($validated['noPinPaymentAmount'])) {
            $validated['noPinPaymentAmount'] = (string) $validated['noPinPaymentAmount'];
        }

        $result = $this->cardService->activatePhysicalCard($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/update-pin',
        operationId: 'updatePin',
        summary: 'Update PIN (physical)',
        description: "Update the PIN for a physical card.\n\n**PIN rules**:\n1. Must be a 6-digit number.\n2. Cannot have 3 or more consecutive repeated digits.\n3. Cannot be an entirely ascending or descending sequence.\n4. Cannot contain repeated two- or three-digit segments (e.g., 123123, 909090, 121212).\n\nThis endpoint supports a webhook notification on status change.\n\n**Status values**: `wait_process` (pending), `processing`, `success`, `fail`\n\nSource: Wasabi Card /merchant/core/mcb/card/physicalCard/updatePin",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'merchantOrderNo', 'pin'],
                properties: [
                    new OA\Property(property: 'cardNo',          type: 'string', example: '38928421021320391244',   description: 'Card id'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'ORDER20250101000000001', description: 'Client transaction id. Length must be between 15 and 65 characters'),
                    new OA\Property(property: 'pin',             type: 'string', example: '654321',                 description: 'New PIN. 6 digits. Rules: no 3+ consecutive repeated digits; not fully ascending/descending; no repeated two- or three-digit segments (e.g., 123123, 909090, 121212)'),
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
                                new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id'),
                                new OA\Property(property: 'cardNo',           type: 'string',  example: '38928421021320391244', description: 'Card id'),
                                new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                 description: 'Currency'),
                                new OA\Property(property: 'amount',           type: 'number',  example: 0,                     description: 'Amount (BigDecimal)'),
                                new OA\Property(property: 'fee',              type: 'number',  example: 0,                     description: 'Fee (BigDecimal)'),
                                new OA\Property(property: 'receivedAmount',   type: 'number',  example: 0,                     description: 'Amount received (BigDecimal)'),
                                new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                 description: 'Received currency'),
                                new OA\Property(property: 'type',             type: 'string',  example: 'update_pin',          description: 'Transaction type. update_pin: Update PIN'),
                                new OA\Property(property: 'status',           type: 'string',  example: 'processing',          description: 'Status: wait_process (pending), processing, success, fail'),
                                new OA\Property(property: 'description',      type: 'string',  example: '', nullable: true,    description: 'Description'),
                                new OA\Property(property: 'remark',           type: 'string',  example: '', nullable: true,    description: 'Transaction notes'),
                                new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function updatePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'          => ['required', 'string'],
            'merchantOrderNo' => ['required', 'string', 'min:15', 'max:65'],
            'pin'             => ['required', 'string', 'digits:6'],
        ]);

        $result = $this->cardService->updatePin($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/purchase-transactions',
        operationId: 'cardPurchaseTransactions',
        summary: 'Card Purchase Transaction',
        description: "Returns a paginated list of card purchase transactions, covering card fees and initial deposit amounts.\n\n**Note**: This endpoint does NOT support webhooks.\n\nUse `startTime` / `endTime` (Unix milliseconds) to filter by trading time.\n\nSource: Wasabi Card /merchant/core/mcb/card/purchaseTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',         type: 'integer', example: 1,                       description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',        type: 'integer', example: 10,                      description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'T1852379826671345664',  description: 'Client transaction id (optional filter)'),
                    new OA\Property(property: 'orderNo',         type: 'string',  example: '1852379830190366720',   description: 'Transaction id (optional filter)'),
                    new OA\Property(property: 'startTime',       type: 'integer', example: 1730476700000,           description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',         type: 'integer', example: 1730476800000,           description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'orderNo',         type: 'string',  example: '1852379830190366720',  description: 'Transaction id'),
                                            new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'T1852379826671345664', nullable: true, description: 'Client transaction id'),
                                            new OA\Property(property: 'cardTypeId',      type: 'integer', example: 111001,                description: 'Card type id'),
                                            new OA\Property(property: 'cardType',        type: 'string',  example: 'Virtual',             description: 'Card type: Virtual, Physical'),
                                            new OA\Property(property: 'organization',    type: 'string',  example: 'Visa',                description: 'Card organization: Visa, MasterCard, Discover'),
                                            new OA\Property(property: 'bankCardBin',     type: 'string',  example: '531993',              description: 'Card Bin. Example: 531993'),
                                            new OA\Property(property: 'currency',        type: 'string',  example: 'USD',                 description: 'Currency'),
                                            new OA\Property(property: 'cardFee',         type: 'number',  example: 5,                     description: 'Card fee (BigDecimal)'),
                                            new OA\Property(property: 'depositAmount',   type: 'number',  example: 20,                    description: 'Initial deposit amount (BigDecimal)'),
                                            new OA\Property(property: 'status',          type: 'string',  example: 'success',             description: 'Transaction status: wait_process (pending), processing, success, fail'),
                                            new OA\Property(property: 'transactionTime', type: 'integer', example: 1730476742000,         description: 'Transaction time (Unix ms)'),
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
    public function cardPurchaseTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'         => ['required', 'integer', 'min:1'],
            'pageSize'        => ['required', 'integer', 'min:1', 'max:100'],
            'merchantOrderNo' => ['nullable', 'string'],
            'orderNo'         => ['nullable', 'string'],
            'startTime'       => ['nullable', 'integer'],
            'endTime'         => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardPurchaseTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/operation-transactions',
        operationId: 'cardOperationTransactions',
        summary: 'Card Operation Transaction',
        description: "Returns a paginated list of card operation transactions.\n\nCovers card lifecycle events: deposit, cancel, freeze (Freeze), unfreeze (UnFreeze), withdraw, update_pin (Update PIN), blocked (Block Card), card_update (Update Card), and overdraft_statement (Card overdraft statement).\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/transaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',         type: 'integer', example: 1,                      description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',        type: 'integer', example: 10,                     description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'type',            type: 'string',  example: 'deposit',              description: 'Transaction type filter (optional). Values: create (Create Card), deposit (Deposit Card), cancel (Cancel Card), Freeze (Freeze Card), UnFreeze (UnFreeze Card), withdraw (Withdraw Card), update_pin (Update PIN), blocked (Block Card), card_update (Update Card), overdraft_statement (Card overdraft statement)'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id (optional filter)'),
                    new OA\Property(property: 'orderNo',         type: 'string',  example: '1852379830190366720',  description: 'Transaction id (optional filter)'),
                    new OA\Property(property: 'cardNo',          type: 'string',  example: 'AA2025032942035903249258024', description: 'Card id (optional filter)'),
                    new OA\Property(property: 'startTime',       type: 'integer', example: 1730476700000,          description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',         type: 'integer', example: 1730476800000,          description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',       description: 'Transaction id'),
                                            new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664',      nullable: true, description: 'Client transaction id'),
                                            new OA\Property(property: 'cardNo',           type: 'string',  example: 'AA2025032942035903249258024', description: 'Card id'),
                                            new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                       description: 'Currency'),
                                            new OA\Property(property: 'amount',           type: 'number',  example: 15,                          description: 'Amount (BigDecimal)'),
                                            new OA\Property(property: 'fee',              type: 'number',  example: 1.5,                         description: 'Fee (BigDecimal)'),
                                            new OA\Property(property: 'receivedAmount',   type: 'number',  example: 15,                          description: 'Amount received (BigDecimal)'),
                                            new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                       description: 'Received currency'),
                                            new OA\Property(property: 'type',             type: 'string',  example: 'deposit',                   description: 'Transaction type'),
                                            new OA\Property(property: 'status',           type: 'string',  example: 'success',                   description: 'Status: wait_process (pending), processing, success, fail'),
                                            new OA\Property(property: 'description',      type: 'string',  example: null, nullable: true,        description: 'Description'),
                                            new OA\Property(property: 'remark',           type: 'string',  example: null, nullable: true,        description: 'Transaction notes'),
                                            new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476742000,               description: 'Transaction time (Unix ms)'),
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
    public function cardOperationTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'         => ['required', 'integer', 'min:1'],
            'pageSize'        => ['required', 'integer', 'min:1', 'max:100'],
            'type'            => ['nullable', 'string'],
            'merchantOrderNo' => ['nullable', 'string'],
            'orderNo'         => ['nullable', 'string'],
            'cardNo'          => ['nullable', 'string'],
            'startTime'       => ['nullable', 'integer'],
            'endTime'         => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardOperationTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/operation-transactions-v2',
        operationId: 'cardOperationTransactionsV2',
        summary: 'Card Operation Transaction-V2',
        description: "Returns a paginated list of card operation transactions — V2 endpoint.\n\n**Note**: Cards created via the deprecated `/merchant/core/mcb/card/openCard` endpoint will NOT appear here — those records live on the Card Purchase Transaction interface.\n\nCovers all V2 card lifecycle events: create (Create Card), deposit (Deposit Card), cancel (Cancel Card), Freeze (Freeze Card), UnFreeze (UnFreeze Card), withdraw (Withdraw Card), update_pin (Update PIN), blocked (Block Card), card_update (Update Card), overdraft_statement (Card overdraft statement).\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/v2/transaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',         type: 'integer', example: 1,                      description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',        type: 'integer', example: 10,                     description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'type',            type: 'string',  example: 'create',               description: 'Transaction type filter (optional). Values: create (Create Card), deposit (Deposit Card), cancel (Cancel Card), Freeze (Freeze Card), UnFreeze (UnFreeze Card), withdraw (Withdraw Card), update_pin (Update PIN), blocked (Block Card), card_update (Update Card), overdraft_statement (Card overdraft statement)'),
                    new OA\Property(property: 'merchantOrderNo', type: 'string',  example: 'T1852379826671345664', description: 'Client transaction id (optional filter)'),
                    new OA\Property(property: 'orderNo',         type: 'string',  example: '1852379830190366720',  description: 'Transaction id (optional filter)'),
                    new OA\Property(property: 'cardNo',          type: 'string',  example: 'WB2025103119841257038691924352', description: 'Card id (optional filter)'),
                    new OA\Property(property: 'startTime',       type: 'integer', example: 1730476700000,          description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',         type: 'integer', example: 1730476800000,          description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'orderNo',          type: 'string',  example: '1852379830190366720',            description: 'Transaction id'),
                                            new OA\Property(property: 'merchantOrderNo',  type: 'string',  example: 'T1852379826671345664',           nullable: true, description: 'Client transaction id'),
                                            new OA\Property(property: 'cardNo',           type: 'string',  example: 'WB2025103119841257038691924352',  description: 'Card id'),
                                            new OA\Property(property: 'currency',         type: 'string',  example: 'USD',                            description: 'Currency'),
                                            new OA\Property(property: 'amount',           type: 'number',  example: 20,                               description: 'Amount (BigDecimal)'),
                                            new OA\Property(property: 'fee',              type: 'number',  example: 0.1,                              description: 'Fee (BigDecimal)'),
                                            new OA\Property(property: 'receivedAmount',   type: 'number',  example: 20,                               description: 'Amount received (BigDecimal)'),
                                            new OA\Property(property: 'receivedCurrency', type: 'string',  example: 'USD',                            description: 'Received currency'),
                                            new OA\Property(property: 'type',             type: 'string',  example: 'create',                         description: 'Transaction type'),
                                            new OA\Property(property: 'status',           type: 'string',  example: 'wait_process',                   description: 'Status: wait_process (pending), processing, success, fail'),
                                            new OA\Property(property: 'description',      type: 'string',  example: null, nullable: true,             description: 'Description'),
                                            new OA\Property(property: 'remark',           type: 'string',  example: null, nullable: true,             description: 'Transaction notes'),
                                            new OA\Property(property: 'transactionTime',  type: 'integer', example: 1730476741729,                    description: 'Transaction time (Unix ms)'),
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
    public function cardOperationTransactionsV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'         => ['required', 'integer', 'min:1'],
            'pageSize'        => ['required', 'integer', 'min:1', 'max:100'],
            'type'            => ['nullable', 'string'],
            'merchantOrderNo' => ['nullable', 'string'],
            'orderNo'         => ['nullable', 'string'],
            'cardNo'          => ['nullable', 'string'],
            'startTime'       => ['nullable', 'integer'],
            'endTime'         => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardOperationTransactionsV2($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/auth-transactions',
        operationId: 'cardAuthorizationTransactions',
        summary: 'Card Authorization Transaction (Consumption Bill)',
        description: "Returns a paginated list of card authorization transactions (consumption bill).\n\n**Type values**:\n- `auth`: Authorization (also known as consumption) — e.g., subscribing to a GPT monthly membership generates a consumption record.\n- `Void`: Reversal. When a user initiates an `auth` transaction but a refund is issued before the acquiring bank and issuing bank settle, this is classed as a reversal.\n- `refund`: When a user initiates an `auth` transaction and both banks have already settled, the user then initiates a refund.\n- `verification`: Card binding verification — e.g., binding a card to Alipay for consume generates a card binding transaction record.\n- `maintain_fee`: Card fee (monthly fee, annual fee, ATM withdraw fee...).\n\n**Status values**:\n- `authorized`: The transaction is still being processed and has not yet been settled.\n- `failed`: The transaction failed.\n- `succeed`: The transaction has been completed.\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/authTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',   type: 'integer', example: 1,                           description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',  type: 'integer', example: 10,                          description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'type',      type: 'string',  example: 'auth',                      description: 'Transaction type filter (optional). Values: auth (Authorization), Void (Reversal), refund, verification, maintain_fee (Card fee)'),
                    new OA\Property(property: 'tradeNo',   type: 'string',  example: 'trans1232435363435463432',  description: 'Transaction serial number (optional filter)'),
                    new OA\Property(property: 'cardNo',    type: 'string',  example: '1242352328671924231',        description: 'Card id (optional filter)'),
                    new OA\Property(property: 'startTime', type: 'integer', example: 1730476700000,               description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',   type: 'integer', example: 1730476800000,               description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1563, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'cardNo',               type: 'string',  example: '1242352328671924231',              description: 'Card id'),
                                            new OA\Property(property: 'tradeNo',              type: 'string',  example: 'trans1232435363435463432',          description: 'Transaction serial number'),
                                            new OA\Property(property: 'originTradeNo',        type: 'string',  example: null, nullable: true,              description: 'Original transaction serial number (for Void/refund types)'),
                                            new OA\Property(property: 'currency',             type: 'string',  example: 'SGD',                             description: 'Transaction currency'),
                                            new OA\Property(property: 'amount',               type: 'number',  example: 16.96,                             description: 'Transaction amount (BigDecimal)'),
                                            new OA\Property(property: 'authorizedAmount',     type: 'number',  example: 2.45,                              description: 'Authorized amount in card currency (BigDecimal)'),
                                            new OA\Property(property: 'authorizedCurrency',   type: 'string',  example: 'USD',                             description: 'Card currency used for authorization'),
                                            new OA\Property(property: 'fee',                  type: 'number',  example: 0.3,                               description: 'Transaction fee (BigDecimal)'),
                                            new OA\Property(property: 'feeCurrency',          type: 'string',  example: 'USD',                             description: 'Fee currency'),
                                            new OA\Property(property: 'crossBoardFee',        type: 'number',  example: 0.2,                               description: 'Cross-border fee (BigDecimal)'),
                                            new OA\Property(property: 'crossBoardFeeCurrency',type: 'string',  example: 'USD',                             description: 'Cross-border fee currency'),
                                            new OA\Property(property: 'settleAmount',         type: 'number',  example: 0,                                 description: 'Settlement amount (BigDecimal)'),
                                            new OA\Property(property: 'settleCurrency',       type: 'string',  example: null, nullable: true,              description: 'Settlement currency'),
                                            new OA\Property(property: 'settleDate',           type: 'string',  example: null, nullable: true,              description: 'Settlement date'),
                                            new OA\Property(property: 'authorizationCode',    type: 'string',  example: '478198',                          description: 'Authorization code'),
                                            new OA\Property(property: 'merchantName',         type: 'string',  example: 'HUQQABAZ RESTAURANTS B DUBAI ARE', description: 'Merchant name'),
                                            new OA\Property(
                                                property: 'merchantData',
                                                type: 'object',
                                                description: 'Merchant details',
                                                properties: [
                                                    new OA\Property(property: 'name',         type: 'string',  example: 'HUQQABAZ RESTAURANTS B DUBAI ARE', description: 'Merchant name'),
                                                    new OA\Property(property: 'categoryCode', type: 'string',  example: '5811',                            description: 'MCC (Merchant Category Code)'),
                                                    new OA\Property(property: 'category',     type: 'string',  example: '',                                description: 'Merchant category'),
                                                    new OA\Property(property: 'country',      type: 'string',  example: 'ARE',                             description: 'Merchant country (ISO 3166-1 alpha-3)'),
                                                    new OA\Property(property: 'state',        type: 'string',  example: '',                                description: 'Merchant state'),
                                                    new OA\Property(property: 'city',         type: 'string',  example: '',                                description: 'Merchant city'),
                                                    new OA\Property(property: 'zipCode',      type: 'string',  example: '',                                description: 'Merchant ZIP code'),
                                                    new OA\Property(property: 'mid',          type: 'integer', example: 213028402482045,                   description: 'Merchant ID'),
                                                    new OA\Property(property: 'walletType',   type: 'string',  example: 'ApplePay',                        description: 'Wallet type used for the transaction (e.g., ApplePay, GooglePay)'),
                                                ]
                                            ),
                                            new OA\Property(property: 'type',            type: 'string',  example: 'auth',          description: 'Transaction type: auth (Authorization), Void (Reversal), refund, verification, maintain_fee'),
                                            new OA\Property(property: 'status',          type: 'string',  example: 'authorized',    description: 'Status: authorized (processing/not yet settled), failed, succeed (completed)'),
                                            new OA\Property(property: 'description',     type: 'string',  example: 'Auth',          description: 'Description'),
                                            new OA\Property(property: 'transactionTime', type: 'integer', example: 1729422898000,   description: 'Transaction time (Unix ms)'),
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
    public function cardAuthorizationTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'   => ['required', 'integer', 'min:1'],
            'pageSize'  => ['required', 'integer', 'min:1', 'max:100'],
            'type'      => ['nullable', 'string'],
            'tradeNo'   => ['nullable', 'string'],
            'cardNo'    => ['nullable', 'string'],
            'startTime' => ['nullable', 'integer'],
            'endTime'   => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardAuthorizationTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/auth-fee-transactions',
        operationId: 'cardAuthFeeTransactions',
        summary: 'Card Authorization Fee Transaction',
        description: "Returns a paginated list of card authorization fee transactions.\n\nIf the user's bank card balance is insufficient to cover the authorization fee, the bank will deduct Wasabi's funds to offset the fee. Wasabi will then debit the merchant's reserve account on the platform to offset the fee.\n\n**Fee Collection Process**:\n1. Bank debits the card balance (deduction fails due to insufficient card balance).\n2. Bank debits Wasabi's reserve funds to offset the fee. The bill is sent to Wasabi.\n3. Wasabi verifies the card balance and debits the card balance to offset the fee (deduction fails due to insufficient card balance).\n4. Wasabi debits the merchant's reserve account on the platform to offset the fee (a fee record will be generated after this link is executed).\n\n**Trade type values**:\n- `card_patch_fee`: Authorization fee\n- `card_patch_cross_border`: Cross border fee\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/authFeeTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',       type: 'integer', example: 1,                              description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',      type: 'integer', example: 10,                             description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'tradeType',     type: 'string',  example: 'card_patch_fee',               description: 'Transaction type of card (optional filter). Values: card_patch_fee (Authorization fee), card_patch_cross_border (Cross border fee)'),
                    new OA\Property(property: 'tradeNo',       type: 'string',  example: 'CAF1232435363435463432',       description: 'Transaction id (optional filter)'),
                    new OA\Property(property: 'originTradeNo', type: 'string',  example: 'trans1232435363435463432',     description: 'Origin authorization transaction serial number (optional filter)'),
                    new OA\Property(property: 'cardNo',        type: 'string',  example: '1242352328671924231',           description: 'Card id (optional filter)'),
                    new OA\Property(property: 'startTime',     type: 'integer', example: 1729422800000,                  description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',       type: 'integer', example: 1729422900000,                  description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1563, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'cardNo',               type: 'string',  example: '1242352328671924231',        description: 'Card id'),
                                            new OA\Property(property: 'tradeNo',              type: 'string',  example: 'CAF1232435363435463432',      description: 'Transaction id'),
                                            new OA\Property(property: 'originTradeNo',        type: 'string',  example: 'trans1232435363435463432',    description: 'Origin authorization transaction serial number'),
                                            new OA\Property(property: 'currency',             type: 'string',  example: 'USD',                         description: 'Currency'),
                                            new OA\Property(property: 'amount',               type: 'number',  example: 0.5,                           description: 'Amount (BigDecimal)'),
                                            new OA\Property(property: 'type',                 type: 'string',  example: 'card_patch_fee',              description: 'Transaction type: card_patch_fee (Authorization fee), card_patch_cross_border (Cross border fee)'),
                                            new OA\Property(property: 'deductionSourceFunds', type: 'string',  example: 'wallet',                      description: 'Source of funds used for deduction'),
                                            new OA\Property(property: 'status',               type: 'string',  example: 'success',                     description: 'Transaction status'),
                                            new OA\Property(property: 'transactionTime',      type: 'integer', example: 1729422898000,                 description: 'Transaction time (Unix ms)'),
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
    public function cardAuthFeeTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'       => ['required', 'integer', 'min:1'],
            'pageSize'      => ['required', 'integer', 'min:1', 'max:100'],
            'tradeType'     => ['nullable', 'string'],
            'tradeNo'       => ['nullable', 'string'],
            'originTradeNo' => ['nullable', 'string'],
            'cardNo'        => ['nullable', 'string'],
            'startTime'     => ['nullable', 'integer'],
            'endTime'       => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->cardAuthFeeTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/3ds-transactions',
        operationId: 'card3dsTransactions',
        summary: 'Card 3DS Transaction',
        description: "Returns a paginated list of card 3DS transactions.\n\n**Type values** (determines the shape of `values` field):\n- `third_3ds_otp`: OTP — `values` contains an encrypted OTP code (plain text example: 204566). No `expirationTime` field.\n- `auth_url`: Transaction authorization response URL — `values` contains an encrypted URL (plain text example: https://www.google.com). `expirationTime` is populated.\n- `activation_code`: Physical card activation code — `values` contains an encrypted activation code (plain text example: 20834698). `currency`, `amount`, and `merchantName` may be null.\n\nAll `values` are encrypted and must be decrypted using the merchant's private key.\n\nThis endpoint supports a webhook notification on status change.\n\nSource: Wasabi Card /merchant/core/mcb/card/third3dsTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',   type: 'integer', example: 1,                          description: 'Current page. Default is 1'),
                    new OA\Property(property: 'pageSize',  type: 'integer', example: 10,                         description: 'Number of records per page. Default 10, maximum 100'),
                    new OA\Property(property: 'type',      type: 'string',  example: 'third_3ds_otp',            description: 'Type filter (optional). Values: third_3ds_otp (OTP), auth_url (Transaction authorization response URL), activation_code (physical card activation code)'),
                    new OA\Property(property: 'tradeNo',   type: 'string',  example: 'trans1232435363435463432', description: 'Transaction serial number (optional filter)'),
                    new OA\Property(property: 'cardNo',    type: 'string',  example: '1242352328671924231',       description: 'Card ID (optional filter)'),
                    new OA\Property(property: 'startTime', type: 'integer', example: 1729422800000,              description: 'Start trading time filter (Unix milliseconds timestamp, optional)'),
                    new OA\Property(property: 'endTime',   type: 'integer', example: 1729422900000,              description: 'End trading time filter (Unix milliseconds timestamp, optional)'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1, description: 'Total number of matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'cardNo',         type: 'string',  example: '1242352328671924231',                       description: 'Card ID'),
                                            new OA\Property(property: 'tradeNo',        type: 'string',  example: 'trans1232435363435463432',                   description: 'Transaction serial number'),
                                            new OA\Property(property: 'originTradeNo',  type: 'string',  example: null, nullable: true,                        description: 'Origin transaction serial number'),
                                            new OA\Property(property: 'currency',       type: 'string',  example: 'CNY',  nullable: true,                      description: 'Transaction currency (null for activation_code type)'),
                                            new OA\Property(property: 'amount',         type: 'string',  example: '16.96',                                     description: 'Transaction amount (BigDecimal as string; "0" for activation_code type)'),
                                            new OA\Property(property: 'merchantName',   type: 'string',  example: 'ULTRA MOBILE', nullable: true,              description: 'Merchant name (Transaction Scenario). Null for activation_code type'),
                                            new OA\Property(property: 'values',         type: 'string',  example: 'ajfon34nNOIN24nafaiw4onnfn0iw32ngfn0IF0Q34NFQFOFAW', description: 'Encrypted payload. Decrypt with merchant private key. third_3ds_otp: OTP code; auth_url: Authorization URL; activation_code: Activation code'),
                                            new OA\Property(property: 'type',           type: 'string',  example: 'third_3ds_otp',                             description: 'Transaction type: third_3ds_otp (OTP), auth_url (Authorization URL), activation_code (Physical card activation code)'),
                                            new OA\Property(property: 'transactionTime',type: 'integer', example: 1729422898000,                              description: 'Transaction time (Unix ms)'),
                                            new OA\Property(property: 'description',    type: 'string',  example: null, nullable: true,                        description: 'Description'),
                                            new OA\Property(property: 'expirationTime', type: 'integer', example: 1729422899000, nullable: true,               description: 'Expiration time (Unix ms). Only populated for auth_url type'),
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
    public function card3dsTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'   => ['required', 'integer', 'min:1'],
            'pageSize'  => ['required', 'integer', 'min:1', 'max:100'],
            'type'      => ['nullable', 'string'],
            'tradeNo'   => ['nullable', 'string'],
            'cardNo'    => ['nullable', 'string'],
            'startTime' => ['nullable', 'integer'],
            'endTime'   => ['nullable', 'integer'],
        ]);

        $result = $this->cardService->card3dsTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/cards/simulate-auth',
        operationId: 'simulateAuthTransaction',
        summary: 'Simulated authorized transaction',
        description: "Simulate an authorized transaction against a card. Intended for **sandbox / testing environments only**.\n\n**Type values**:\n- `auth`: Transaction authorization\n- `refund`: Transaction refund\n- `Void`: Transaction cancellation\n- `maintain_fee`: Card fee\n\n`originSerialNumber` is required when `type` is `Void` or `refund` — it specifies the original transaction ID to cancel or refund.\n\nReturns an empty data object `{}` on success.\n\nSource: Wasabi Card /merchant/core/mcb/card/simulateAuthTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Card'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cardNo', 'type', 'amount', 'currency'],
                properties: [
                    new OA\Property(property: 'cardNo',             type: 'string',  example: '1242352328671924231', description: 'Card ID'),
                    new OA\Property(property: 'type',               type: 'string',  example: 'auth',               description: 'Transaction type. Values: auth (Transaction authorization), refund (Transaction refund), Void (Transaction cancellation), maintain_fee (Card fee)'),
                    new OA\Property(property: 'amount',             type: 'number',  example: 10.00,                description: 'Transaction amount (BigDecimal)'),
                    new OA\Property(property: 'currency',           type: 'string',  example: 'USD',                description: 'Transaction currency'),
                    new OA\Property(property: 'originSerialNumber', type: 'string',  example: 'trans123456',        description: 'Cancel or refund the specified original transaction ID. Required when type is Void or refund (optional)'),
                    new OA\Property(property: 'description',        type: 'string',  example: 'Test auth',          description: 'Transaction description (optional)'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response — returns empty data object',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(property: 'data',    type: 'object',  example: '{}', description: 'Empty object on success'),
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
    public function simulateAuthTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardNo'             => ['required', 'string'],
            'type'               => ['required', 'string', 'in:auth,refund,Void,maintain_fee'],
            'amount'             => ['required', 'numeric', 'gt:0'],
            'currency'           => ['required', 'string', 'size:3'],
            'originSerialNumber' => ['nullable', 'string'],
            'description'        => ['nullable', 'string'],
        ]);

        $validated['amount'] = (string) $validated['amount'];

        $result = $this->cardService->simulateAuthTransaction($validated);

        return $this->success($result);
    }
}
