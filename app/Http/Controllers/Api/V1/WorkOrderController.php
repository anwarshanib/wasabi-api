<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WasabiCard\WorkOrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes Wasabi Card WORK ORDER endpoints to third-party clients.
 */
final class WorkOrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WorkOrderService $workOrderService,
    ) {}

    #[OA\Post(
        path: '/api/v1/work-orders',
        operationId: 'submitWorkOrder',
        summary: 'Submit Work Order',
        description: 'Submit a new work order to the Wasabi Card platform (e.g. card activation, dispute). Returns the created work order details. Source: Wasabi Card /merchant/core/mcb/work/submit',
        security: [['ApiKeyAuth' => []]],
        tags: ['Work Orders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantOrderNo', 'title', 'target', 'content', 'tradeType'],
                properties: [
                    new OA\Property(
                        property: 'merchantOrderNo',
                        type: 'string',
                        example: '13243897979979797999008085',
                        description: 'Merchant\'s unique order reference number'
                    ),
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        example: 'ApplePay',
                        description: 'Short descriptive title for the work order'
                    ),
                    new OA\Property(
                        property: 'target',
                        type: 'string',
                        example: '5533700042831234',
                        description: 'Target identifier — card number or account reference'
                    ),
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        example: 'Active',
                        description: 'Detailed content / description of the work order'
                    ),
                    new OA\Property(
                        property: 'tradeType',
                        type: 'string',
                        example: 'CARD_ACTIVE',
                        description: 'Work order type (e.g. CARD_ACTIVE)'
                    ),
                    new OA\Property(
                        property: 'remark',
                        type: 'string',
                        example: null,
                        nullable: true,
                        description: 'Optional supplementary notes'
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work order submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code',    type: 'integer', example: 200),
                        new OA\Property(property: 'msg',     type: 'string',  example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'merchantOrderNo', type: 'string',  example: '13243897979979797999008085', description: 'Merchant order reference'),
                                new OA\Property(property: 'orderNo',         type: 'string',  example: 'WORK-2025080719534',         description: 'Wasabi platform work order number'),
                                new OA\Property(property: 'title',           type: 'string',  example: 'ApplePay'),
                                new OA\Property(property: 'target',          type: 'string',  example: '5533700042831234'),
                                new OA\Property(property: 'content',         type: 'string',  example: 'Active'),
                                new OA\Property(property: 'tradeType',       type: 'string',  example: 'CARD_ACTIVE'),
                                new OA\Property(property: 'tradeStatus',     type: 'string',  example: 'processing', description: 'Current status: processing, success, failed'),
                                new OA\Property(property: 'remark',          type: 'string',  example: null, nullable: true),
                                new OA\Property(property: 'createTime',      type: 'integer', example: 1754607865000, description: 'Unix timestamp (ms)'),
                                new OA\Property(property: 'updateTime',      type: 'integer', example: 1754648044000, description: 'Unix timestamp (ms)'),
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
    public function submitWorkOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo' => ['required', 'string', 'max:64'],
            'title'           => ['required', 'string', 'max:128'],
            'target'          => ['required', 'string', 'max:64'],
            'content'         => ['required', 'string', 'max:512'],
            'tradeType'       => ['required', 'string', 'max:64'],
            'remark'          => ['nullable', 'string', 'max:256'],
        ]);

        $result = $this->workOrderService->submit($validated);

        return $this->success($result);
    }

    #[OA\Get(
        path: '/api/v1/work-orders',
        operationId: 'listWorkOrders',
        summary: 'Work Order List',
        description: 'Retrieve a paginated list of work orders with optional filters. Source: Wasabi Card /merchant/core/mcb/work/list',
        security: [['ApiKeyAuth' => []]],
        tags: ['Work Orders'],
        parameters: [
            new OA\Parameter(name: 'merchantOrderNo', in: 'query', required: false, description: 'Filter by merchant order reference number', schema: new OA\Schema(type: 'string', example: '13243897979979797999008085')),
            new OA\Parameter(name: 'orderNo',         in: 'query', required: false, description: 'Filter by Wasabi platform work order number', schema: new OA\Schema(type: 'string', example: 'WORK-202508071953472304731676672')),
            new OA\Parameter(name: 'tradeType',       in: 'query', required: false, description: 'Filter by work order type (e.g. CARD_ACTIVE)', schema: new OA\Schema(type: 'string', example: 'CARD_ACTIVE')),
            new OA\Parameter(name: 'tradeStatus',     in: 'query', required: false, description: 'Filter by status: processing, success, failed', schema: new OA\Schema(type: 'string', example: 'success')),
            new OA\Parameter(name: 'pageNo',          in: 'query', required: false, description: 'Page number (1-based)', schema: new OA\Schema(type: 'integer', example: 1, minimum: 1)),
            new OA\Parameter(name: 'pageSize',        in: 'query', required: false, description: 'Records per page (default 10)', schema: new OA\Schema(type: 'integer', example: 10, minimum: 1, maximum: 100)),
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
                                new OA\Property(property: 'total', type: 'integer', example: 1, description: 'Total matching records'),
                                new OA\Property(
                                    property: 'records',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'merchantOrderNo', type: 'string',  example: '13243897979979797999008085'),
                                            new OA\Property(property: 'orderNo',         type: 'string',  example: 'WORK-202508071953472304731676672'),
                                            new OA\Property(property: 'title',           type: 'string',  example: 'ApplePay'),
                                            new OA\Property(property: 'target',          type: 'string',  example: '5533700042831234'),
                                            new OA\Property(property: 'content',         type: 'string',  example: 'Active'),
                                            new OA\Property(property: 'tradeType',       type: 'string',  example: 'CARD_ACTIVE'),
                                            new OA\Property(property: 'tradeStatus',     type: 'string',  example: 'success'),
                                            new OA\Property(property: 'description',     type: 'string',  example: 'SUCCESS'),
                                            new OA\Property(property: 'remark',          type: 'string',  example: null, nullable: true),
                                            new OA\Property(property: 'createTime',      type: 'integer', example: 1754607865000, description: 'Unix timestamp (ms)'),
                                            new OA\Property(property: 'updateTime',      type: 'integer', example: 1754648044000, description: 'Unix timestamp (ms)'),
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
            new OA\Response(response: 429, description: 'Rate limit exceeded',        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 502, description: 'Upstream Wasabi API error',  content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function listWorkOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantOrderNo' => ['nullable', 'string', 'max:64'],
            'orderNo'         => ['nullable', 'string', 'max:64'],
            'tradeType'       => ['nullable', 'string', 'max:64'],
            'tradeStatus'     => ['nullable', 'string', 'max:32'],
            'pageNo'          => ['nullable', 'integer', 'min:1'],
            'pageSize'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->workOrderService->list($validated);

        return $this->success($result);
    }
}
