<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wraps the Wasabi Card WORK ORDER API endpoints.
 *
 * Work orders are transactional (e.g. card activations, disputes) — responses
 * are never cached as statuses change continuously.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/work/submit
 *   POST /merchant/core/mcb/work/list
 */
final class WorkOrderService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Submit a new work order to the Wasabi platform.
     *
     * @param  array{
     *     merchantOrderNo: string,
     *     title: string,
     *     target: string,
     *     content: string,
     *     tradeType: string,
     *     remark?: string|null,
     * } $params
     *
     * @return array<string, mixed>
     */
    public function submit(array $params): array
    {
        $body = [
            'merchantOrderNo' => $params['merchantOrderNo'],
            'title'           => $params['title'],
            'target'          => $params['target'],
            'content'         => $params['content'],
            'tradeType'       => $params['tradeType'],
        ];

        if (! empty($params['remark'])) {
            $body['remark'] = $params['remark'];
        }

        return $this->client->post('/merchant/core/mcb/work/submit', $body)['data'];
    }

    /**
     * Retrieve a paginated list of work orders with optional filters.
     *
     * @param  array{
     *     merchantOrderNo?: string|null,
     *     orderNo?: string|null,
     *     tradeType?: string|null,
     *     tradeStatus?: string|null,
     *     pageNo?: int,
     *     pageSize?: int,
     * } $params
     *
     * @return array{total: int, records: array<int, array<string, mixed>>}
     */
    public function list(array $params): array
    {
        $body = [
            'pageNo'   => (int) ($params['pageNo'] ?? 1),
            'pageSize' => (int) ($params['pageSize'] ?? 10),
        ];

        if (! empty($params['merchantOrderNo'])) {
            $body['merchantOrderNo'] = $params['merchantOrderNo'];
        }

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (! empty($params['tradeType'])) {
            $body['tradeType'] = $params['tradeType'];
        }

        if (! empty($params['tradeStatus'])) {
            $body['tradeStatus'] = $params['tradeStatus'];
        }

        return $this->client->post('/merchant/core/mcb/work/list', $body)['data'];
    }
}
