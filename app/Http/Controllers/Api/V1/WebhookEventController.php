<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Exposes stored Wasabi webhook events to third-party clients.
 *
 * Third parties call this to poll for the final async result of operations
 * like cardholder creation, card activation, or wallet deposits.
 *
 * Flow:
 *   Third Party  →  GET /api/v1/webhook-events  →  This controller  →  webhook_events table
 */
final class WebhookEventController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/webhook-events',
        operationId: 'listWebhookEvents',
        summary: 'Webhook Event List',
        description: "Returns a paginated list of inbound Wasabi webhook events received by this server.\n\nUse this endpoint to poll for the **final async result** of operations like cardholder creation, card activation, or wallet deposits. Wasabi pushes the final status asynchronously — the initial API response contains a pending status (e.g. `wait_audit`), and the final result (e.g. `pass_audit` or `reject`) arrives via webhook and is stored here.\n\n**How to use:**\n1. Call e.g. `POST /api/v1/cardholders/create-v2` → receive `{ status: 'wait_audit' }`\n2. Poll this endpoint filtering by `category=card_holder` and `reference_id={holderId}` until a final status appears.\n\n**Available `category` values:**\n- `card_holder` — Cardholder create/update v2 status changes\n- `card_holder_change_email` — Cardholder email update status\n- `card_transaction` — Card deposit/withdraw/cancel final result\n- `card_auth_transaction` — Card authorization (may be pushed multiple times as status flows)\n- `card_fee_patch` — Authorization fee applied\n- `card_3ds` — 3DS OTP code, auth URL, or activation code\n- `physical_card` — Physical card activation result\n- `work` — Work order status\n- `wallet_transaction` — Wallet deposit/withdrawal\n- `wallet_transaction_v2` — Wallet transaction history v2\n\n**`reference_id` values by category:**\n- `card_holder`, `card_holder_change_email` → `holderId`\n- `card_auth_transaction`, `card_fee_patch`, `card_3ds` → `tradeNo`\n- `card_transaction`, `work`, `wallet_transaction`, `wallet_transaction_v2` → `orderNo`\n- `physical_card` → `cardNo`",
        security: [['ApiKeyAuth' => []]],
        tags: ['Webhook Events'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 60),
                description: 'Filter by event category. Example: card_holder',
                example: 'card_holder'
            ),
            new OA\Parameter(
                name: 'reference_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
                description: 'Filter by primary entity ID. Meaning depends on category: holderId for card_holder; tradeNo for card_auth_transaction/card_3ds/card_fee_patch; orderNo for card_transaction/work/wallet; cardNo for physical_card',
                example: '123456'
            ),
            new OA\Parameter(
                name: 'merchant_order_no',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 60),
                description: 'Filter by merchantOrderNo from the event payload',
                example: 'ORDER202501010000000001'
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 40),
                description: 'Filter by status from the event payload. Examples: pass_audit, reject, wait_audit, success, fail',
                example: 'pass_audit'
            ),
            new OA\Parameter(
                name: 'from',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter events received on or after this datetime. Format: Y-m-d H:i:s',
                example: '2026-04-01 00:00:00'
            ),
            new OA\Parameter(
                name: 'to',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter events received on or before this datetime. Format: Y-m-d H:i:s',
                example: '2026-04-30 23:59:59'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15),
                description: 'Number of records per page. Maximum 100',
                example: 15
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
                description: 'Page number',
                example: 1
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
                                new OA\Property(property: 'total',        type: 'integer', example: 42,  description: 'Total matching records'),
                                new OA\Property(property: 'per_page',     type: 'integer', example: 15),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page',    type: 'integer', example: 3),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id',                 type: 'integer', example: 1),
                                            new OA\Property(property: 'request_id',         type: 'string',  example: 'wsb-req-abc123', nullable: true, description: 'Wasabi unique request ID (X-WSB-REQUEST-ID)'),
                                            new OA\Property(property: 'category',           type: 'string',  example: 'card_holder', description: 'Event category (X-WSB-CATEGORY)'),
                                            new OA\Property(property: 'reference_id',       type: 'string',  example: '124024', nullable: true, description: 'Primary entity ID extracted from payload'),
                                            new OA\Property(property: 'merchant_order_no',  type: 'string',  example: 'ORDER202501010000000001', nullable: true, description: 'merchantOrderNo from payload'),
                                            new OA\Property(property: 'status',             type: 'string',  example: 'pass_audit', nullable: true, description: 'Status from payload (status or tradeStatus field)'),
                                            new OA\Property(property: 'signature_verified', type: 'boolean', example: true, description: 'Whether the X-WSB-SIGNATURE RSA verification passed'),
                                            new OA\Property(property: 'payload',            type: 'object',  description: 'Full raw event payload from Wasabi. Structure varies per category — see Wasabi webhook documentation'),
                                            new OA\Property(property: 'created_at',         type: 'string',  example: '2026-04-18T10:00:00.000000Z', description: 'Timestamp when this event was received'),
                                            new OA\Property(property: 'updated_at',         type: 'string',  example: '2026-04-18T10:00:00.000000Z'),
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
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category'          => ['nullable', 'string', 'max:60'],
            'reference_id'      => ['nullable', 'string', 'max:100'],
            'merchant_order_no' => ['nullable', 'string', 'max:60'],
            'status'            => ['nullable', 'string', 'max:40'],
            'from'              => ['nullable', 'date_format:Y-m-d H:i:s'],
            'to'                => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:from'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'              => ['nullable', 'integer', 'min:1'],
        ]);

        $query = WebhookEvent::query()
            ->where('api_token_id', $request->attributes->get('api_token')->id)
            ->latest('created_at');

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (! empty($validated['reference_id'])) {
            $query->where('reference_id', $validated['reference_id']);
        }

        if (! empty($validated['merchant_order_no'])) {
            $query->where('merchant_order_no', $validated['merchant_order_no']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['from'])) {
            $query->where('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->where('created_at', '<=', $validated['to']);
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $result  = $query->paginate($perPage);

        return $this->success([
            'total'        => $result->total(),
            'per_page'     => $result->perPage(),
            'current_page' => $result->currentPage(),
            'last_page'    => $result->lastPage(),
            'data'         => $result->items(),
        ]);
    }
}
