<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wraps the Wasabi Card WALLET API endpoints.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/account/walletDeposit           (Deprecated)
 *   POST /merchant/core/mcb/account/walletDepositTransaction (Deprecated)
 *   POST /merchant/core/mcb/wallet/v2/coins
 *   POST /merchant/core/mcb/wallet/v2/create
 *   POST /merchant/core/mcb/wallet/v2/addressList
 *   POST /merchant/core/mcb/wallet/v2/transaction
 */
final class WalletService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Place a wallet recharge order.
     *
     * Returns an order number, deposit address, and amounts to send.
     *
     * @param  array{
     *     amount: string,
     *     chain?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     userInputDepositAmount: float|int,
     *     actualDepositAmount: float|int,
     *     currency: string,
     *     chain: string,
     *     toAddress: string,
     *     createTime: int,
     *     expireSecond: int,
     * }
     */
    public function walletDeposit(array $params): array
    {
        $body = ['amount' => $params['amount']];

        if (! empty($params['chain'])) {
            $body['chain'] = $params['chain'];
        }

        return $this->client->post('/merchant/core/mcb/account/walletDeposit', $body)['data'] ?? [];
    }

    /**
     * Query wallet deposit transaction history (paginated).
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     orderNo?: string|null,
     *     fromAddress?: string|null,
     *     toAddress?: string|null,
     *     txId?: string|null,
     *     status?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{total: int, records: array<int, array<string, mixed>>}
     */
    public function walletDepositTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) ($params['pageNum'] ?? 1),
            'pageSize' => (int) ($params['pageSize'] ?? 10),
        ];

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (! empty($params['fromAddress'])) {
            $body['fromAddress'] = $params['fromAddress'];
        }

        if (! empty($params['toAddress'])) {
            $body['toAddress'] = $params['toAddress'];
        }

        if (! empty($params['txId'])) {
            $body['txId'] = $params['txId'];
        }

        if (! empty($params['status'])) {
            $body['status'] = $params['status'];
        }

        if (! empty($params['startTime'])) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (! empty($params['endTime'])) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/account/walletDepositTransaction', $body)['data'] ?? [];
    }

    /**
     * Return the list of supported coins/chains for wallet operations.
     *
     * @return array<int, array{
     *     coinKey: string,
     *     chain: string,
     *     coinFullName: string,
     *     coinName: string,
     *     showCoinDecimal: int,
     *     coinDecimal: int,
     *     blockChainShowName: string,
     *     browser: string,
     *     txRefUrl: string,
     *     contractAddress: string,
     *     enableDeposit: bool,
     *     enableWithdraw: bool,
     *     confirmations: int,
     *     enabled: bool,
     * }>
     */
    public function coinListV2(): array
    {
        return $this->client->post('/merchant/core/mcb/wallet/v2/coins')['data'] ?? [];
    }

    /**
     * Create a wallet deposit address for a given coin/chain.
     *
     * Only supported when the coin's enabled=true.
     *
     * @param  string  $coinKey  e.g. "USDT_BEP20"
     * @return array{coinKey: string, chain: string, coinName: string, address: string}
     */
    public function createWalletAddressV2(string $coinKey): array
    {
        return $this->client->post('/merchant/core/mcb/wallet/v2/create', [
            'coinKey' => $coinKey,
        ])['data'] ?? [];
    }

    /**
     * Return all wallet addresses created for the merchant.
     *
     * @return array<int, array{coinKey: string, chain: string, coinName: string, address: string}>
     */
    public function walletAddressListV2(): array
    {
        return $this->client->post('/merchant/core/mcb/wallet/v2/addressList')['data'] ?? [];
    }

    /**
     * Return paginated wallet transaction history (deposits and withdrawals).
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     coinKey?: string|null,
     *     coinName?: string|null,
     *     txHash?: string|null,
     *     sourceAddress?: string|null,
     *     destinationAddress?: string|null,
     *     orderNo?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{total: int, records: array<int, array<string, mixed>>}
     */
    public function walletTransactionHistoryV2(array $params): array
    {
        $body = [
            'pageNum'  => (int) ($params['pageNum'] ?? 1),
            'pageSize' => (int) ($params['pageSize'] ?? 10),
        ];

        foreach (['coinKey', 'coinName', 'txHash', 'sourceAddress', 'destinationAddress', 'orderNo', 'type', 'status'] as $field) {
            if (! empty($params[$field])) {
                $body[$field] = $params[$field];
            }
        }

        if (! empty($params['startTime'])) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (! empty($params['endTime'])) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/wallet/v2/transaction', $body)['data'] ?? [];
    }
}
