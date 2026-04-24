<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wraps the Wasabi Card CARD API endpoints.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/card/v2/cardTypes  (Support Bins)
 *   POST /merchant/core/mcb/card/openCard      (Create Card — Deprecated)
 */
class CardService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Return all supported card types (bins) available to the merchant.
     *
     * @return array<int, array{
     *     cardTypeId: int,
     *     organization: string,
     *     country: string,
     *     mode: string,
     *     bankCardBin: string,
     *     type: string,
     *     category: string,
     *     cardName: string,
     *     cardDesc: string,
     *     cardPrice: float|int,
     *     cardPriceCurrency: string,
     *     support: array<mixed>,
     *     risk: array<mixed>,
     * }>
     */
    public function supportBins(): array
    {
        return $this->client->post('/merchant/core/mcb/card/v2/cardTypes')['data'] ?? [];
    }

    /**
     * Return a single card type matched by cardTypeId, or null if not found.
     *
     * Caches the full bins list for 5 minutes to avoid an extra Wasabi API call
     * on every card creation request.
     */
    public function getCardTypeById(int $cardTypeId): ?array
    {
        foreach ($this->supportBins() as $bin) {
            if ((int) ($bin['cardTypeId'] ?? 0) === $cardTypeId) {
                return $bin;
            }
        }

        return null;
    }

    /**
     * Create (open) a new card — Deprecated endpoint.
     *
     * CardNo is only returned when status=success.
     *
     * @param  array{
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     holderId?: int|null,
     *     amount?: string|null,
     *     cardNumber?: string|null,
     *     accountId?: int|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string|null,
     *     currency: string,
     *     amount: string,
     *     fee: string,
     *     receivedAmount: string,
     *     status: string,
     * }
     */
    public function createCardDeprecated(array $params): array
    {
        $body = [
            'merchantOrderNo' => $params['merchantOrderNo'],
            'cardTypeId'      => (int) $params['cardTypeId'],
        ];

        if (! empty($params['holderId'])) {
            $body['holderId'] = (int) $params['holderId'];
        }

        if (isset($params['amount']) && $params['amount'] !== null && $params['amount'] !== '') {
            $body['amount'] = $params['amount'];
        }

        if (! empty($params['cardNumber'])) {
            $body['cardNumber'] = $params['cardNumber'];
        }

        if (! empty($params['accountId'])) {
            $body['accountId'] = (int) $params['accountId'];
        }

        return $this->client->post('/merchant/core/mcb/card/openCard', $body)['data'] ?? [];
    }

    /**
     * Create (open) a new card — V2 endpoint.
     *
     * `amount` is required in V2. `designId` is a new optional field for
     * white-label and gift card brand customisation.
     *
     * @param  array{
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     amount: string,
     *     holderId?: int|null,
     *     cardNumber?: string|null,
     *     accountId?: int|null,
     *     designId?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function createCardV2(array $params): array
    {
        $body = [
            'merchantOrderNo' => $params['merchantOrderNo'],
            'cardTypeId'      => (int) $params['cardTypeId'],
            'amount'          => $params['amount'],
        ];

        if (! empty($params['holderId'])) {
            $body['holderId'] = (int) $params['holderId'];
        }

        if (! empty($params['cardNumber'])) {
            $body['cardNumber'] = $params['cardNumber'];
        }

        if (! empty($params['accountId'])) {
            $body['accountId'] = (int) $params['accountId'];
        }

        if (! empty($params['designId'])) {
            $body['designId'] = $params['designId'];
        }

        return $this->client->post('/merchant/core/mcb/card/v2/createCard', $body)['data'] ?? [];
    }

    /**
     * Retrieve card information.
     *
     * When `onlySimpleInfo=true` (default), balance information is excluded
     * from the response to improve performance.
     *
     * @param  array{
     *     cardNo: string,
     *     onlySimpleInfo?: bool|null,
     * } $params
     *
     * @return array{
     *     cardTypeId: int,
     *     cardBankBin: string,
     *     holderId: int|null,
     *     cardNo: string,
     *     status: string,
     *     blocked: bool,
     *     bindTime: int,
     *     remark: string|null,
     *     noPinPaymentAmount: float|int,
     *     balanceInfo: array{cardNo: string, amount: float|int, usedAmount: float|int, currency: string},
     *     customCategory: string,
     *     holderInfo: array{firstName: string, lastName: string, country: string, state: string, town: string, address: string, addressLine2: string, postCode: string},
     *     spendingControls: array<int, array{interval: string, amount: float|int, supportSetting: bool}>,
     *     riskControls: array{allowedMcc: array<int, string>},
     * }
     */
    public function cardInfo(array $params): array
    {
        $body = ['cardNo' => $params['cardNo']];

        if (isset($params['onlySimpleInfo'])) {
            $body['onlySimpleInfo'] = (bool) $params['onlySimpleInfo'];
        }

        return $this->client->post('/merchant/core/mcb/card/info', $body)['data'] ?? [];
    }

    /**
     * Retrieve sensitive card information (card number, CVV, expiry, activate URL).
     *
     * All returned values are encrypted with the user's public key and must be
     * decrypted using the merchant's private key.
     * Gift cards do NOT return cardNumber, cvv, or expireDate; they DO return activateUrl.
     *
     * @param  array{cardNo: string} $params
     *
     * @return array{
     *     cardNumber: string,
     *     cvv: string,
     *     expireDate: string,
     *     activateUrl: string|null,
     * }
     */
    public function cardInfoForSensitive(array $params): array
    {
        return $this->client->post('/merchant/core/mcb/card/sensitive', ['cardNo' => $params['cardNo']])['data'] ?? [];
    }

    /**
     * Retrieve the available balance for a specific card.
     *
     * Note: some card bins do not support usedAmount.
     *
     * @param  array{cardNo: string} $params
     *
     * @return array{
     *     cardNo: string,
     *     amount: float|int,
     *     usedAmount: float|int,
     *     currency: string,
     * }
     */
    public function cardBalance(array $params): array
    {
        return $this->client->post('/merchant/core/mcb/card/balanceInfo', ['cardNo' => $params['cardNo']])['data'] ?? [];
    }

    /**
     * Return a paginated list of cards for the merchant.
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     cardNo?: string|null,
     *     status?: string|null,
     *     cardTypeId?: int|null,
     *     holderId?: int|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardList(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (! empty($params['status'])) {
            $body['status'] = $params['status'];
        }

        if (! empty($params['cardTypeId'])) {
            $body['cardTypeId'] = (int) $params['cardTypeId'];
        }

        if (! empty($params['holderId'])) {
            $body['holderId'] = (int) $params['holderId'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/list', $body)['data'] ?? [];
    }

    /**
     * Update card attributes: noPinPaymentAmount, spendingControls, riskControls, clientRemark.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     clientRemark?: string|null,
     *     noPinPaymentAmount?: string|null,
     *     spendingControls?: array<int, array{interval: string, amount: string}>|null,
     *     riskControls?: array{allowedMcc?: array<int,string>, blockedMcc?: array<int,string>}|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function updateCard(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
        ];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        if (isset($params['noPinPaymentAmount']) && $params['noPinPaymentAmount'] !== null) {
            $body['noPinPaymentAmount'] = $params['noPinPaymentAmount'];
        }

        if (! empty($params['spendingControls'])) {
            $body['spendingControls'] = $params['spendingControls'];
        }

        if (isset($params['riskControls']) && $params['riskControls'] !== null) {
            $body['riskControls'] = $params['riskControls'];
        }

        return $this->client->post('/merchant/core/mcb/card/updateAttribute', $body)['data'] ?? [];
    }

    /**
     * Update the client remark (note) on a card.
     *
     * Returns the full updated card info object.
     *
     * @param  array{
     *     cardNo: string,
     *     clientRemark?: string|null,
     * } $params
     *
     * @return array<string, mixed>
     */
    public function updateNote(array $params): array
    {
        $body = ['cardNo' => $params['cardNo']];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        return $this->client->post('/merchant/core/mcb/card/note', $body)['data'] ?? [];
    }

    /**
     * Freeze a card — V2 endpoint.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     clientRemark?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string|null,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function freezeCardV2(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
        ];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        return $this->client->post('/merchant/core/mcb/card/v2/freeze', $body)['data'] ?? [];
    }

    /**
     * Unfreeze a card — V2 endpoint.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     clientRemark?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string|null,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function unfreezeCardV2(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
        ];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        return $this->client->post('/merchant/core/mcb/card/v2/unfreeze', $body)['data'] ?? [];
    }

    /**
     * Deposit funds into a card.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     amount: string,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function depositCard(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'amount'          => $params['amount'],
        ];

        return $this->client->post('/merchant/core/mcb/card/deposit', $body)['data'] ?? [];
    }

    /**
     * Withdraw funds from a card.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     amount: string,
     *     clientRemark?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function withdrawCard(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'amount'          => $params['amount'],
        ];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        return $this->client->post('/merchant/core/mcb/card/withdraw', $body)['data'] ?? [];
    }

    /**
     * Cancel a card.
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     clientRemark?: string|null,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function cancelCard(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
        ];

        if (isset($params['clientRemark']) && $params['clientRemark'] !== null) {
            $body['clientRemark'] = $params['clientRemark'];
        }

        return $this->client->post('/merchant/core/mcb/card/cancel', $body)['data'] ?? [];
    }

    /**
     * Activate a physical card.
     *
     * @param  array{
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     pin: string,
     *     activeCode: string,
     *     noPinPaymentAmount?: string|null,
     * } $params
     *
     * @return array{
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     type: string,
     *     status: string,
     *     remark: string|null,
     * }
     */
    public function activatePhysicalCard(array $params): array
    {
        $body = [
            'merchantOrderNo' => $params['merchantOrderNo'],
            'cardNo'          => $params['cardNo'],
            'pin'             => $params['pin'],
            'activeCode'      => $params['activeCode'],
        ];

        if (isset($params['noPinPaymentAmount']) && $params['noPinPaymentAmount'] !== null) {
            $body['noPinPaymentAmount'] = $params['noPinPaymentAmount'];
        }

        return $this->client->post('/merchant/core/mcb/card/physicalCard/activeCard', $body)['data'] ?? [];
    }

    /**
     * Update the PIN for a physical card.
     *
     * PIN rules: 6 digits, no 3+ consecutive repeated digits, not ascending/descending
     * sequence, no repeated two- or three-digit segments (e.g., 123123, 909090, 121212).
     *
     * @param  array{
     *     cardNo: string,
     *     merchantOrderNo: string,
     *     pin: string,
     * } $params
     *
     * @return array{
     *     orderNo: string,
     *     merchantOrderNo: string,
     *     cardNo: string,
     *     currency: string,
     *     amount: float|int,
     *     fee: float|int,
     *     receivedAmount: float|int,
     *     receivedCurrency: string,
     *     type: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     *     transactionTime: int,
     * }
     */
    public function updatePin(array $params): array
    {
        $body = [
            'cardNo'          => $params['cardNo'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'pin'             => $params['pin'],
        ];

        return $this->client->post('/merchant/core/mcb/card/physicalCard/updatePin', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card purchase transactions.
     *
     * Returns records related to card fees and initial deposit amounts.
     * This endpoint does NOT support webhooks.
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     merchantOrderNo?: string|null,
     *     orderNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardPurchaseTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['merchantOrderNo'])) {
            $body['merchantOrderNo'] = $params['merchantOrderNo'];
        }

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/purchaseTransaction', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card operation transactions (V1).
     *
     * Covers deposit, cancel, freeze, unfreeze, withdraw, update_pin, block,
     * card_update, and overdraft_statement events.
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     type?: string|null,
     *     merchantOrderNo?: string|null,
     *     orderNo?: string|null,
     *     cardNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardOperationTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['type'])) {
            $body['type'] = $params['type'];
        }

        if (! empty($params['merchantOrderNo'])) {
            $body['merchantOrderNo'] = $params['merchantOrderNo'];
        }

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/transaction', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card operation transactions — V2 endpoint.
     *
     * Cards created via the deprecated /merchant/core/mcb/card/openCard endpoint
     * will NOT appear here (those records are on the Card Purchase Transaction interface).
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     type?: string|null,
     *     merchantOrderNo?: string|null,
     *     orderNo?: string|null,
     *     cardNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardOperationTransactionsV2(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['type'])) {
            $body['type'] = $params['type'];
        }

        if (! empty($params['merchantOrderNo'])) {
            $body['merchantOrderNo'] = $params['merchantOrderNo'];
        }

        if (! empty($params['orderNo'])) {
            $body['orderNo'] = $params['orderNo'];
        }

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/v2/transaction', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card authorization transactions (consumption bill).
     *
     * Types: auth (Authorization/Consumption), Void (Reversal), refund, verification,
     * maintain_fee (Card fee: monthly fee, annual fee, ATM withdraw fee...).
     *
     * Status: authorized (still being processed / not yet settled),
     * failed, succeed (completed).
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     type?: string|null,
     *     tradeNo?: string|null,
     *     cardNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardAuthorizationTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['type'])) {
            $body['type'] = $params['type'];
        }

        if (! empty($params['tradeNo'])) {
            $body['tradeNo'] = $params['tradeNo'];
        }

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/authTransaction', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card authorization fee transactions.
     *
     * If the user's card balance is insufficient to cover the authorization fee,
     * Wasabi will debit the merchant's reserve account on the platform to offset it.
     *
     * Trade types: card_patch_fee (Authorization fee), card_patch_cross_border (Cross border fee).
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     tradeType?: string|null,
     *     tradeNo?: string|null,
     *     originTradeNo?: string|null,
     *     cardNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardAuthFeeTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['tradeType'])) {
            $body['tradeType'] = $params['tradeType'];
        }

        if (! empty($params['tradeNo'])) {
            $body['tradeNo'] = $params['tradeNo'];
        }

        if (! empty($params['originTradeNo'])) {
            $body['originTradeNo'] = $params['originTradeNo'];
        }

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/authFeeTransaction', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of card 3DS transactions.
     *
     * Three sub-types exist depending on the `type` field:
     *   - third_3ds_otp : OTP code (values = encrypted OTP)
     *   - auth_url      : Transaction authorization response URL (values = encrypted URL; expirationTime is populated)
     *   - activation_code: Physical card activation code (values = encrypted activation code)
     *
     * This endpoint supports a webhook notification on status change.
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     type?: string|null,
     *     tradeNo?: string|null,
     *     cardNo?: string|null,
     *     startTime?: int|null,
     *     endTime?: int|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function card3dsTransactions(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['type'])) {
            $body['type'] = $params['type'];
        }

        if (! empty($params['tradeNo'])) {
            $body['tradeNo'] = $params['tradeNo'];
        }

        if (! empty($params['cardNo'])) {
            $body['cardNo'] = $params['cardNo'];
        }

        if (isset($params['startTime']) && $params['startTime'] !== null) {
            $body['startTime'] = (int) $params['startTime'];
        }

        if (isset($params['endTime']) && $params['endTime'] !== null) {
            $body['endTime'] = (int) $params['endTime'];
        }

        return $this->client->post('/merchant/core/mcb/card/third3dsTransaction', $body)['data'] ?? [];
    }

    /**
     * Simulate an authorized transaction against a card (sandbox / testing only).
     *
     * Supported types: auth (Transaction authorization), refund (Transaction refund),
     * Void (Transaction cancellation), maintain_fee (Card fee).
     *
     * `originSerialNumber`: Required when type is Void or refund — cancel/refund the
     * specified original transaction ID.
     *
     * @param  array{
     *     cardNo: string,
     *     type: string,
     *     amount: string,
     *     currency: string,
     *     originSerialNumber?: string|null,
     *     description?: string|null,
     * } $params
     *
     * @return array<string, mixed>
     */
    public function simulateAuthTransaction(array $params): array
    {
        $body = [
            'cardNo'   => $params['cardNo'],
            'type'     => $params['type'],
            'amount'   => $params['amount'],
            'currency' => $params['currency'],
        ];

        if (! empty($params['originSerialNumber'])) {
            $body['originSerialNumber'] = $params['originSerialNumber'];
        }

        if (isset($params['description']) && $params['description'] !== null) {
            $body['description'] = $params['description'];
        }

        return $this->client->post('/merchant/core/mcb/card/simulateAuthTransaction', $body)['data'] ?? [];
    }
}
