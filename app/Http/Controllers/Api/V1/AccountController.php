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
}
