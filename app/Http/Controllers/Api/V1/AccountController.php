<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\AccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card ACCOUNT endpoints to third-party clients.
 */
final class AccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    #[OA\Get(
        path: '/api/v1/accounts/assets',
        operationId: 'getAssets',
        summary: 'Assets',
        description: 'Returns all accounts with live balances for the merchant. Data is real-time and never cached. Source: Wasabi Card /merchant/core/mcb/account/info',
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
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
                            items: new OA\Items(ref: '#/components/schemas/AccountObject')
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
    public function assets(): JsonResponse
    {
        $assets = $this->accountService->getAssets();

        return $this->success($assets);
    }

    #[OA\Get(
        path: '/api/v1/accounts',
        operationId: 'getAccountList',
        summary: 'Account List',
        description: 'Returns a paginated list of accounts with optional filters. Data is real-time and never cached. Source: Wasabi Card /merchant/core/mcb/account/list',
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                in: 'query',
                required: false,
                description: 'Filter by account ID',
                schema: new OA\Schema(type: 'integer', example: 19847563867367666)
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                description: 'Filter by account type: WALLET (Wallet Account) or MARGIN (Margin Account)',
                schema: new OA\Schema(type: 'string', enum: ['WALLET', 'MARGIN'], example: 'WALLET')
            ),
            new OA\Parameter(
                name: 'pageNum',
                in: 'query',
                required: true,
                description: 'Current page number. Default is 1',
                schema: new OA\Schema(type: 'integer', example: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'pageSize',
                in: 'query',
                required: true,
                description: 'Number of records per page. Default 10, maximum 10',
                schema: new OA\Schema(type: 'integer', example: 10, minimum: 1, maximum: 10)
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
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total',   type: 'integer', example: 21, description: 'Total matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/AccountObject')
                                ),
                            ]
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
    public function accountList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountId' => ['nullable', 'integer'],
            'type'      => ['nullable', 'string', 'in:WALLET,MARGIN'],
            'pageNum'   => ['required', 'integer', 'min:1'],
            'pageSize'  => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $result = $this->accountService->getAccountList($validated);

        return $this->success($result);
    }

    #[OA\Get(
        path: '/api/v1/accounts/single',
        operationId: 'getSingleAccount',
        summary: 'Single Account Query',
        description: 'Returns the details of a single account by accountId. Data is real-time and never cached. Source: Wasabi Card /merchant/core/mcb/account/single/query',
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                in: 'query',
                required: true,
                description: 'The account ID to query',
                schema: new OA\Schema(type: 'integer', example: 1996967874372661250)
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
                            items: new OA\Items(ref: '#/components/schemas/AccountObject')
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
    public function singleAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountId' => ['required', 'integer'],
        ]);

        $result = $this->accountService->getSingleAccount((int) $validated['accountId']);

        return $this->success($result);
    }

    #[OA\Get(
        path: '/api/v1/accounts/transactions',
        operationId: 'getLedgerTransactions',
        summary: 'Ledger Transactions',
        description: "Returns a paginated ledger transaction history for a specific account.\n\n`direction`: IN = credit, OUT = debit.\n\n`startTime` / `endTime`: Unix timestamp in milliseconds.\n\nSource: Wasabi Card /merchant/core/mcb/account/transaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
        parameters: [
            new OA\Parameter(
                name: 'pageNum',
                in: 'query',
                required: true,
                description: 'Current page number. Default is 1',
                schema: new OA\Schema(type: 'integer', example: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'pageSize',
                in: 'query',
                required: true,
                description: 'Number of records per page. Default 10, maximum 100',
                schema: new OA\Schema(type: 'integer', example: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'orderNo',
                in: 'query',
                required: false,
                description: 'Filter by transaction id',
                schema: new OA\Schema(type: 'string', example: 'C2C_UZS_2025122307584616966')
            ),
            new OA\Parameter(
                name: 'accountId',
                in: 'query',
                required: false,
                description: 'Filter by account ID',
                schema: new OA\Schema(type: 'integer', example: 1979009257233215490)
            ),
            new OA\Parameter(
                name: 'bizType',
                in: 'query',
                required: false,
                description: 'Filter by transaction type. Values: chain_deposit, chain_withdraw, card_purchase, card_deposit, card_withdraw, card_cancel, card_auth_fee_patch, card_cross_board_patch, gt_transfer, fixed, card_overdraft_statement, gt_transfer_refund, card_settlement, card_refund, fund_transfer, fund_collection, card_decline_fee',
                schema: new OA\Schema(type: 'string', example: 'gt_transfer')
            ),
            new OA\Parameter(
                name: 'startTime',
                in: 'query',
                required: false,
                description: 'Start transaction time. Unix timestamp (milliseconds). Time range cannot exceed 30 days. Default queries the most recent 30 days.',
                schema: new OA\Schema(type: 'integer', example: 1766480100000)
            ),
            new OA\Parameter(
                name: 'endTime',
                in: 'query',
                required: false,
                description: 'End transaction time. Unix timestamp (milliseconds). Time range cannot exceed 30 days. Default queries the most recent 30 days.',
                schema: new OA\Schema(type: 'integer', example: 1766480200000)
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
                        new OA\Property(property: 'msg',     type: 'string',  example: 'SUCCESS'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 34, description: 'Total matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'txId',           type: 'integer', example: 517581,                       description: 'Transaction ID'),
                                            new OA\Property(property: 'accountId',      type: 'string',  example: '1979009257233215490',         description: 'Account ID'),
                                            new OA\Property(property: 'currency',       type: 'string',  example: 'USD',                        description: 'ISO 4217 currency code'),
                                            new OA\Property(property: 'amount',         type: 'string',  example: '4.08',                       description: 'Transaction amount (BigDecimal)'),
                                            new OA\Property(property: 'beforeBalance',  type: 'string',  example: '99327.04375',                description: 'Balance before transaction (BigDecimal)'),
                                            new OA\Property(property: 'afterBalance',   type: 'string',  example: '99322.96375',                description: 'Balance after transaction (BigDecimal)'),
                                            new OA\Property(property: 'orderNo',        type: 'string',  example: 'C2C_UZS_2025122307584616966', description: 'Associated order number'),
                                            new OA\Property(property: 'bizType',        type: 'string',  example: 'gt_transfer',                description: 'Business type of the transaction'),
                                            new OA\Property(property: 'direction',      type: 'string',  example: 'OUT',                        description: 'IN = credit, OUT = debit'),
                                            new OA\Property(property: 'remark',         type: 'string',  example: 'Global transfer',            description: 'Transaction remark'),
                                            new OA\Property(property: 'createTime',     type: 'integer', example: 1766480100874,                description: 'Unix timestamp (ms)'),
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
    public function ledgerTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'   => ['required', 'integer', 'min:1'],
            'pageSize'  => ['required', 'integer', 'min:1', 'max:100'],
            'orderNo'   => ['nullable', 'string', 'max:64'],
            'accountId' => ['nullable', 'integer'],
            'bizType'   => ['nullable', 'string', 'max:64'],
            'startTime' => ['nullable', 'integer'],
            'endTime'   => ['nullable', 'integer'],
        ]);

        $result = $this->accountService->getLedgerTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/accounts/create',
        operationId: 'createSharedAccount',
        summary: 'Create a shared account',
        description: 'Creates a new shared MARGIN account for the merchant. Returns the newly created account details. Source: Wasabi Card /merchant/core/mcb/account/create',
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['accountName'],
                properties: [
                    new OA\Property(
                        property: 'accountName',
                        type: 'string',
                        example: 'margin9980',
                        description: 'Shared account name'
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
                            items: new OA\Items(ref: '#/components/schemas/AccountObject')
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
    public function createSharedAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountName' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->accountService->createSharedAccount($validated['accountName']);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/accounts/transfer',
        operationId: 'fundTransfer',
        summary: 'Fund transfer',
        description: "Transfer funds between accounts.\n\n`type`: TRANSFER = transfer between accounts; COLLECTION = fund collection.\n\n`merchantOrderNo`: Client-side unique transaction ID, length must be between 20 and 40 characters.\n\n`payerAccountId`: Payer account (typically WALLET type).\n\n`payeeAccountId`: Payee account (typically MARGIN type).\n\nSource: Wasabi Card /merchant/core/mcb/account/transfer",
        security: [['ApiKeyAuth' => []]],
        tags: ['Account'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'merchantOrderNo', 'amount', 'payerAccountId', 'payeeAccountId'],
                properties: [
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['TRANSFER', 'COLLECTION'],
                        example: 'TRANSFER',
                        description: 'Operation type: TRANSFER = represents transfer; COLLECTION = represents collection'
                    ),
                    new OA\Property(
                        property: 'merchantOrderNo',
                        type: 'string',
                        example: '20250101TRANSFER00000001234',
                        description: 'Client transaction id. Length must be between 20 and 40 characters'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'string',
                        example: '100.00',
                        description: 'Amount of operation (BigDecimal as string to preserve precision)'
                    ),
                    new OA\Property(
                        property: 'payerAccountId',
                        type: 'integer',
                        example: 1979009257233215490,
                        description: 'Payer ID, typically an account ID of type WALLET'
                    ),
                    new OA\Property(
                        property: 'payeeAccountId',
                        type: 'integer',
                        example: 1996967874372661250,
                        description: 'Payee ID, usually an account ID of type MARGIN'
                    ),
                    new OA\Property(
                        property: 'remark',
                        type: 'string',
                        example: 'Monthly allocation',
                        description: 'Optional remark for the transfer'
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
                        new OA\Property(property: 'data',    type: 'boolean', example: true, description: 'true when transfer is accepted'),
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
    public function fundTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'            => ['required', 'string', 'in:TRANSFER,COLLECTION'],
            'merchantOrderNo' => ['required', 'string', 'min:20', 'max:40'],
            'amount'          => ['required', 'numeric', 'gt:0'],
            'payerAccountId'  => ['required', 'integer'],
            'payeeAccountId'  => ['required', 'integer'],
            'remark'          => ['nullable', 'string', 'max:255'],
        ]);

        // Cast amount to string to preserve BigDecimal precision upstream
        $validated['amount'] = (string) $validated['amount'];

        $result = $this->accountService->fundTransfer($validated);

        return $this->success($result);
    }
}
