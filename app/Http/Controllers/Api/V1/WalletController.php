<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card WALLET endpoints to third-party clients.
 */
final class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    #[OA\Post(
        path: '/api/v1/wallet/deposit',
        operationId: 'walletDeposit',
        summary: 'Wallet Address (Deprecated)',
        description: "**Deprecated** — Place an order for wallet recharge. Returns the order number, deposit address, and the actual amount to send.\n\nSource: Wasabi Card /merchant/core/mcb/account/walletDeposit",
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
        deprecated: true,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(
                        property: 'amount',
                        type: 'string',
                        example: '20',
                        description: 'Deposit amount (BigDecimal as string)'
                    ),
                    new OA\Property(
                        property: 'chain',
                        type: 'string',
                        enum: ['TRC20', 'BEP20'],
                        example: 'TRC20',
                        description: 'Network chain. Default: TRC20'
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response — deposit order created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'orderNo',                 type: 'string',  example: 'CND1896547520597221376', description: 'Platform order number'),
                                new OA\Property(property: 'userInputDepositAmount',  type: 'number',  example: 20,                      description: 'Amount specified by the user'),
                                new OA\Property(property: 'actualDepositAmount',     type: 'number',  example: 20.0377,                 description: 'Actual on-chain amount to send (includes network fees)'),
                                new OA\Property(property: 'currency',                type: 'string',  example: 'USDT',                  description: 'Coin/currency'),
                                new OA\Property(property: 'chain',                   type: 'string',  example: 'TRC20',                 description: 'Blockchain network'),
                                new OA\Property(property: 'toAddress',               type: 'string',  example: 'TF9fZHD27TmEznSRHcirWkXj2asg24kl3jg', description: 'Destination wallet address to send funds to'),
                                new OA\Property(property: 'createTime',              type: 'integer', example: 1741007139000,           description: 'Order creation time (Unix ms)'),
                                new OA\Property(property: 'expireSecond',            type: 'integer', example: 10800,                   description: 'Seconds until the deposit order expires (e.g. 10800 = 3 hours)'),
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
    public function walletDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'chain'  => ['nullable', 'string', 'in:TRC20,BEP20'],
        ]);

        // Cast amount to string to preserve BigDecimal precision upstream
        $validated['amount'] = (string) $validated['amount'];

        $result = $this->walletService->walletDeposit($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/wallet/deposit/transactions',
        operationId: 'walletDepositTransactions',
        summary: 'Wallet Deposit Transaction (Deprecated)',
        description: "**Deprecated** — Query wallet deposit transaction history (paginated).\n\nNote: `status=fail` is not a final state — it may be manually updated to `status=success`.\n\n`startTime` / `endTime`: Unix timestamp in milliseconds.\n\nSource: Wasabi Card /merchant/core/mcb/account/walletDepositTransaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
        deprecated: true,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(
                        property: 'pageNum',
                        type: 'integer',
                        example: 1,
                        minimum: 1,
                        description: 'Current page number. Default is 1'
                    ),
                    new OA\Property(
                        property: 'pageSize',
                        type: 'integer',
                        example: 10,
                        minimum: 1,
                        maximum: 100,
                        description: 'Number of records per page. Default 10, maximum 100'
                    ),
                    new OA\Property(
                        property: 'orderNo',
                        type: 'string',
                        example: 'CND1985645689502720000',
                        description: 'Filter by transaction id (order number)'
                    ),
                    new OA\Property(
                        property: 'fromAddress',
                        type: 'string',
                        example: 'TVwdXFHzD5mJP52xkxtRfVCLrWNaLGiiaB',
                        description: 'Filter by source address'
                    ),
                    new OA\Property(
                        property: 'toAddress',
                        type: 'string',
                        example: 'TF9fZHk27TmEznSRHiirWkX23zbZJC299M',
                        description: 'Filter by target address'
                    ),
                    new OA\Property(
                        property: 'txId',
                        type: 'string',
                        example: 'b5eccb05e227fab979182905e3ff1ec8a0995f43bc407aaaaaaaaaaaaaaa',
                        description: 'Filter by tx hash'
                    ),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['wait_process', 'processing', 'success', 'fail'],
                        example: 'success',
                        description: 'Filter by status. wait_process: Wait process; processing: Processing; success: Success; fail: Failed (fail is not a final state)'
                    ),
                    new OA\Property(
                        property: 'startTime',
                        type: 'integer',
                        example: 1762249798000,
                        description: 'Order start time. Unix timestamp (milliseconds)'
                    ),
                    new OA\Property(
                        property: 'endTime',
                        type: 'integer',
                        example: 1762249920000,
                        description: 'Order end time. Unix timestamp (milliseconds)'
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
                        new OA\Property(property: 'msg',     type: 'string',  example: 'SUCCESS'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 25, description: 'Total matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'orderNo',               type: 'string',  example: 'CND1985645689502720000',                                              description: 'Platform order number'),
                                            new OA\Property(property: 'needDepositTxAmount',   type: 'number',  example: 20,                                                                   description: 'On-chain amount required to complete the deposit'),
                                            new OA\Property(property: 'txAmount',              type: 'number',  example: 20,                                                                   description: 'Actual on-chain transaction amount'),
                                            new OA\Property(property: 'feeRate',               type: 'number',  example: 1.5,                                                                  description: 'Fee rate (percentage)'),
                                            new OA\Property(property: 'fee',                   type: 'number',  example: 0.3,                                                                  description: 'Calculated fee amount'),
                                            new OA\Property(property: 'fixedFee',              type: 'number',  example: 0,                                                                    description: 'Fixed fee amount'),
                                            new OA\Property(property: 'currency',              type: 'string',  example: 'USDT',                                                               description: 'Coin/currency'),
                                            new OA\Property(property: 'receivedAmount',        type: 'number',  example: 19.7,                                                                 description: 'Net amount credited to the account after fees'),
                                            new OA\Property(property: 'receivedCurrency',      type: 'string',  example: 'USD',                                                                description: 'Currency credited to the account'),
                                            new OA\Property(property: 'chain',                 type: 'string',  example: 'TRC20',                                                              description: 'Blockchain network'),
                                            new OA\Property(property: 'fromAddress',           type: 'string',  example: 'TVwdXFHzD5mJP52xkxtRfVCLrWNaLGiiaB',                               description: 'Sender wallet address'),
                                            new OA\Property(property: 'toAddress',             type: 'string',  example: 'TF9fZHk27TmEznSRHiirWkX23zbZJC299M',                               description: 'Receiver wallet address'),
                                            new OA\Property(property: 'txId',                  type: 'string',  example: 'b5eccb05e227fab979182905e3ff1ec8a0995f43bc407aaaaaaaaaaaaaaa', description: 'Blockchain transaction hash'),
                                            new OA\Property(property: 'block',                 type: 'integer', example: 65567635,                                                             description: 'Block number containing the transaction'),
                                            new OA\Property(property: 'confirmTime',           type: 'integer', example: 1727351235000,                                                        description: 'Block confirmation time (Unix ms)'),
                                            new OA\Property(property: 'type',                  type: 'string',  example: 'chain_deposit',                                                      description: 'Transaction type'),
                                            new OA\Property(property: 'status',                type: 'string',  example: 'success',                                                            description: 'Transaction status: wait_process, processing, success, fail (fail is not final)'),
                                            new OA\Property(property: 'remark',                type: 'string',  example: 'Chain deposit',                                                      description: 'Transaction remark'),
                                            new OA\Property(property: 'createTime',            type: 'integer', example: 1762249798000,                                                        description: 'Record creation time (Unix ms)'),
                                            new OA\Property(property: 'updateTime',            type: 'integer', example: 1762249920000,                                                        description: 'Last update time (Unix ms)'),
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
    public function walletDepositTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'     => ['required', 'integer', 'min:1'],
            'pageSize'    => ['required', 'integer', 'min:1', 'max:100'],
            'orderNo'     => ['nullable', 'string', 'max:64'],
            'fromAddress' => ['nullable', 'string', 'max:128'],
            'toAddress'   => ['nullable', 'string', 'max:128'],
            'txId'        => ['nullable', 'string', 'max:128'],
            'status'      => ['nullable', 'string', 'in:wait_process,processing,success,fail'],
            'startTime'   => ['nullable', 'integer'],
            'endTime'     => ['nullable', 'integer'],
        ]);

        $result = $this->walletService->walletDepositTransactions($validated);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/wallet/v2/coins',
        operationId: 'coinListV2',
        summary: 'Coin List-v2',
        description: 'Returns the list of all supported coins and chains available for wallet operations. Source: Wasabi Card /merchant/core/mcb/wallet/v2/coins',
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
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
                                    new OA\Property(property: 'coinKey',            type: 'string',  example: 'USDT_BEP20',                          description: 'Coin key'),
                                    new OA\Property(property: 'chain',              type: 'string',  example: 'BEP20',                               description: 'Chain'),
                                    new OA\Property(property: 'coinFullName',       type: 'string',  example: 'Tether',                              description: 'Coin full name'),
                                    new OA\Property(property: 'coinName',           type: 'string',  example: 'USDT',                                description: 'Coin name'),
                                    new OA\Property(property: 'showCoinDecimal',    type: 'integer', example: 8,                                     description: 'Coin show decimal'),
                                    new OA\Property(property: 'coinDecimal',        type: 'integer', example: 18,                                    description: 'Coin transaction decimal'),
                                    new OA\Property(property: 'blockChainShowName', type: 'string',  example: 'BNB Smart Chain (BEP20)',              description: 'Block chain show name'),
                                    new OA\Property(property: 'browser',            type: 'string',  example: 'https://bscscan.com/token/0x55d398326f99059ff775485246999027b3197955?a={address}', description: 'Wallet address redirect link url'),
                                    new OA\Property(property: 'txRefUrl',           type: 'string',  example: 'https://bscscan.com/tx/{txHash}',     description: 'Transaction redirect link url'),
                                    new OA\Property(property: 'contractAddress',    type: 'string',  example: '0x55d398326f99059ff775485246999027b3197955', description: 'Contract'),
                                    new OA\Property(property: 'enableDeposit',      type: 'boolean', example: true,                                  description: 'Enable deposit'),
                                    new OA\Property(property: 'enableWithdraw',     type: 'boolean', example: true,                                  description: 'Enable withdraw'),
                                    new OA\Property(property: 'confirmations',      type: 'integer', example: 15,                                    description: 'Block confirmation count'),
                                    new OA\Property(property: 'enabled',            type: 'boolean', example: true,                                  description: 'Enable Coin'),
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
    public function coinListV2(): JsonResponse
    {
        $result = $this->walletService->coinListV2();

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/wallet/v2/create',
        operationId: 'createWalletAddressV2',
        summary: 'Create Wallet Address-v2',
        description: "Create a wallet deposit address for a given coin and chain.\n\nOnly supported when the coin's `enabled=true`. You can also automatically generate a wallet address by selecting the coin and chain in the dashboard.\n\nSource: Wasabi Card /merchant/core/mcb/wallet/v2/create",
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['coinKey'],
                properties: [
                    new OA\Property(
                        property: 'coinKey',
                        type: 'string',
                        example: 'USDT_BEP20',
                        description: 'Coin key (from Coin List-v2, e.g. USDT_BEP20, USDT_TRC20)'
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response — wallet address created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'SUCCESS'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'coinKey',  type: 'string', example: 'USDT_BEP20',                                      description: 'Coin key'),
                                new OA\Property(property: 'chain',    type: 'string', example: 'BEP20',                                           description: 'Chain'),
                                new OA\Property(property: 'coinName', type: 'string', example: 'USDT',                                            description: 'Coin name'),
                                new OA\Property(property: 'address',  type: 'string', example: '0xAAac5f0d8133424d86D5Ef3f170E0420e561125f',    description: 'Address'),
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
    public function createWalletAddressV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coinKey' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->walletService->createWalletAddressV2($validated['coinKey']);

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/wallet/v2/address-list',
        operationId: 'walletAddressListV2',
        summary: 'Wallet Address List-v2',
        description: 'Returns all wallet deposit addresses created for the merchant. Source: Wasabi Card /merchant/core/mcb/wallet/v2/addressList',
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
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
                                    new OA\Property(property: 'coinKey',  type: 'string', example: 'USDT_BEP20',                                   description: 'Coin key'),
                                    new OA\Property(property: 'chain',    type: 'string', example: 'BEP20',                                        description: 'Chain'),
                                    new OA\Property(property: 'coinName', type: 'string', example: 'USDT',                                         description: 'Coin name'),
                                    new OA\Property(property: 'address',  type: 'string', example: '0xAAac5f0d8133424d86D5Ef3f170E0420e561125f', description: 'Address'),
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
    public function walletAddressListV2(): JsonResponse
    {
        $result = $this->walletService->walletAddressListV2();

        return $this->success($result);
    }

    #[OA\Post(
        path: '/api/v1/wallet/v2/transactions',
        operationId: 'walletTransactionHistoryV2',
        summary: 'Wallet Transaction History-v2',
        description: "Returns paginated wallet transaction history (deposits and withdrawals).\n\n`type`: DEPOSIT or WITHDRAW.\n\n`status`: wait_process = Pending; processing = Processing; success = Success; fail = Failed.\n\n`startTime` / `endTime`: Unix timestamp (milliseconds). Time range cannot exceed 90 days. Default queries the most recent 90 days.\n\nSource: Wasabi Card /merchant/core/mcb/wallet/v2/transaction",
        security: [['ApiKeyAuth' => []]],
        tags: ['Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pageNum', 'pageSize'],
                properties: [
                    new OA\Property(property: 'pageNum',            type: 'integer', example: 1,           minimum: 1,   description: 'Current page. Default: 1'),
                    new OA\Property(property: 'pageSize',           type: 'integer', example: 10,          minimum: 1, maximum: 100, description: 'Number of pages per page. Default: 10; Maximum: 100'),
                    new OA\Property(property: 'coinKey',            type: 'string',  example: 'USDT_TRC20',              description: 'Coin key'),
                    new OA\Property(property: 'coinName',           type: 'string',  example: 'USDT',                    description: 'Coin name'),
                    new OA\Property(property: 'txHash',             type: 'string',  example: '0x5e3ab80d...',           description: 'Tx hash'),
                    new OA\Property(property: 'sourceAddress',      type: 'string',  example: 'TK4ykR48cQ...',          description: 'Source address'),
                    new OA\Property(property: 'destinationAddress', type: 'string',  example: 'TReJ9Yfvmp...',          description: 'Destination address'),
                    new OA\Property(property: 'orderNo',            type: 'string',  example: 'CND2031235349498847232', description: 'Wasabi transaction id'),
                    new OA\Property(property: 'type',               type: 'string',  enum: ['DEPOSIT', 'WITHDRAW'], example: 'DEPOSIT', description: 'Type: DEPOSIT or WITHDRAW'),
                    new OA\Property(property: 'status',             type: 'string',  enum: ['wait_process', 'processing', 'success', 'fail'], example: 'success', description: 'Status: wait_process = Pending; processing = Processing; success = Success; fail = Failed'),
                    new OA\Property(property: 'startTime',          type: 'integer', example: 1773119220000,            description: 'Order start time. Millisecond timestamp. Range cannot exceed 90 days'),
                    new OA\Property(property: 'endTime',            type: 'integer', example: 1773231300000,            description: 'Order end time. Millisecond timestamp. Range cannot exceed 90 days'),
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
                                new OA\Property(property: 'total', type: 'integer', example: 5, description: 'Total matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'orderNo',             type: 'string',  example: 'CND2031235349498847232',                                                                         description: 'Wasabi transaction id'),
                                            new OA\Property(property: 'coinKey',             type: 'string',  example: 'USDT_TRC20',                                                                                     description: 'Coin key'),
                                            new OA\Property(property: 'block',               type: 'integer', example: 80817428,                                                                                         description: 'Block number'),
                                            new OA\Property(property: 'sourceAddress',       type: 'string',  example: 'TK4ykR48cQQoyFcZ5N4xZCbsBaHcg6n3gJ',                                                          description: 'Source address'),
                                            new OA\Property(property: 'destinationAddress',  type: 'string',  example: 'TReJ9YfvmpPTXuq3kzQneXDco1fQprqZ9v',                                                          description: 'Destination address'),
                                            new OA\Property(property: 'txHash',              type: 'string',  example: '1c19a9635e8c11c6b7be0e402017e81e64e9f7f1ad1a10e1ea1304955745b434',                           description: 'Tx hash'),
                                            new OA\Property(property: 'transactionTime',     type: 'integer', example: 1773119090000,                                                                                    description: 'Transaction time (Unix ms)'),
                                            new OA\Property(property: 'confirmTime',         type: 'integer', example: 1773119194000,                                                                                    description: 'Block confirmation time (Unix ms)'),
                                            new OA\Property(property: 'coinName',            type: 'string',  example: 'USDT',                                                                                           description: 'Coin name'),
                                            new OA\Property(property: 'txAmount',            type: 'string',  example: '9',                                                                                              description: 'On-chain transaction amount (BigDecimal)'),
                                            new OA\Property(property: 'feeRate',             type: 'string',  example: '1.5',                                                                                            description: 'Fee rate (percentage)'),
                                            new OA\Property(property: 'fee',                 type: 'string',  example: '0.135',                                                                                          description: 'Calculated fee amount (BigDecimal)'),
                                            new OA\Property(property: 'fixedFee',            type: 'string',  example: '0',                                                                                              description: 'Fixed fee amount (BigDecimal)'),
                                            new OA\Property(property: 'receivedAmount',      type: 'string',  example: '8.86',                                                                                           description: 'Net amount credited after fees (BigDecimal)'),
                                            new OA\Property(property: 'receivedCurrency',    type: 'string',  example: 'USD',                                                                                            description: 'Currency credited to the account'),
                                            new OA\Property(property: 'type',                type: 'string',  example: 'DEPOSIT',                                                                                        description: 'DEPOSIT or WITHDRAW'),
                                            new OA\Property(property: 'status',              type: 'string',  example: 'success',                                                                                        description: 'Transaction status'),
                                            new OA\Property(property: 'createTime',          type: 'integer', example: 1773119220000,                                                                                    description: 'Record creation time (Unix ms)'),
                                            new OA\Property(property: 'updateTime',          type: 'integer', example: 1773119220000,                                                                                    description: 'Last update time (Unix ms)'),
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
    public function walletTransactionHistoryV2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pageNum'            => ['required', 'integer', 'min:1'],
            'pageSize'           => ['required', 'integer', 'min:1', 'max:100'],
            'coinKey'            => ['nullable', 'string', 'max:64'],
            'coinName'           => ['nullable', 'string', 'max:32'],
            'txHash'             => ['nullable', 'string', 'max:128'],
            'sourceAddress'      => ['nullable', 'string', 'max:128'],
            'destinationAddress' => ['nullable', 'string', 'max:128'],
            'orderNo'            => ['nullable', 'string', 'max:64'],
            'type'               => ['nullable', 'string', 'in:DEPOSIT,WITHDRAW'],
            'status'             => ['nullable', 'string', 'in:wait_process,processing,success,fail'],
            'startTime'          => ['nullable', 'integer'],
            'endTime'            => ['nullable', 'integer'],
        ]);

        $result = $this->walletService->walletTransactionHistoryV2($validated);

        return $this->success($result);
    }
}
