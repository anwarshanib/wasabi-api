<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wraps the Wasabi Card ACCOUNT API endpoints.
 *
 * Account data contains live balances — responses are never cached.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/account/info
 *   POST /merchant/core/mcb/account/list
 */
final class AccountService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Return the merchant's asset summary — all accounts with live balances.
     *
     * @return array<int, array{
     *     accountId: string,
     *     accountName: string,
     *     accountType: string,
     *     currency: string,
     *     totalBalance: float|int,
     *     availableBalance: float|int,
     *     frozenBalance: float|int,
     *     digital: int,
     * }>
     */
    public function getAssets(): array
    {
        return $this->client->post('/merchant/core/mcb/account/info')['data'];
    }

    /**
     * Return a paginated list of accounts with optional filters.
     *
     * @param  array{
     *     accountId?: int|null,
     *     type?: string|null,
     *     pageNum: int,
     *     pageSize: int,
     * } $params
     *
     * @return array{total: int, records: array<int, array<string, mixed>>}
     */
    public function getAccountList(array $params): array
    {
        $body = [
            'pageNum'  => (int) ($params['pageNum'] ?? 1),
            'pageSize' => (int) ($params['pageSize'] ?? 10),
        ];

        if (! empty($params['accountId'])) {
            $body['accountId'] = (int) $params['accountId'];
        }

        if (! empty($params['type'])) {
            $body['type'] = $params['type'];
        }

        return $this->client->post('/merchant/core/mcb/account/list', $body)['data'];
    }
}
