<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wrap the Wasabi Card ACCOUNT API endpoints.
 *
 * Account data contains live balances — responses are never cached.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/account/info
 *   POST /merchant/core/mcb/account/list
 *   POST /merchant/core/mcb/account/single/query
 *   POST /merchant/core/mcb/account/transaction
 *   POST /merchant/core/mcb/account/create
 *   POST /merchant/core/mcb/account/transfer
 */
class AccountService
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
        return $this->client->post('/merchant/core/mcb/account/info')['data'] ?? [];
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

        return $this->client->post('/merchant/core/mcb/account/list', $body)['data'] ?? [];
    }

    /**
     * Return a single account's details by accountId.
     *
     * @param  int  $accountId
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
    public function getSingleAccount(int $accountId): array
    {
        return $this->client->post('/merchant/core/mcb/account/single/query', [
            'accountId' => $accountId,
        ])['data'] ?? [];
    }

    /**
     * Return a paginated ledger transaction history for an account.
     *
     * @param  array{
     *     accountId: int,
     *     pageNum: int,
     *     pageSize: int,
     *     direction?: string|null,
     *     bizType?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{total: int, records: array<int, array<string, mixed>>}
     */
    public function getLedgerTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) ($params['pageNum'] ?? 1),
            'pageSize' => (int) ($params['pageSize'] ?? 10),
        ];

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (! empty($params['accountId'])) {
            $body['accountId'] = (int) $params['accountId'];
        }

        if (! empty($params['bizType'])) {
            $body['bizType'] = $params['bizType'];
        }

        if (! empty($params['startTime'])) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (! empty($params['endTime'])) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/account/transaction', $body)['data'] ?? [];
    }

    /**
     * Create a new shared (MARGIN) account.
     *
     * @param  string  $accountName  Shared account name
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
    public function createSharedAccount(string $accountName): array
    {
        return $this->client->post('/merchant/core/mcb/account/create', [
            'accountName' => $accountName,
        ])['data'] ?? [];
    }

    /**
     * Transfer funds between accounts (WALLET → MARGIN or collection).
     *
     * @param  array{
     *     type: string,
     *     merchantOrderNo: string,
     *     amount: string,
     *     payerAccountId: int,
     *     payeeAccountId: int,
     *     remark?: string|null,
     * } $params
     *
     * @return bool
     */
    public function fundTransfer(array $params): bool
    {
        $body = [
            'type'            => $params['type'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'amount'          => $params['amount'],
            'payerAccountId'  => (int) $params['payerAccountId'],
            'payeeAccountId'  => (int) $params['payeeAccountId'],
        ];

        if (! empty($params['remark'])) {
            $body['remark'] = $params['remark'];
        }

        return (bool) ($this->client->post('/merchant/core/mcb/account/transfer', $body)['data'] ?? false);
    }
}
