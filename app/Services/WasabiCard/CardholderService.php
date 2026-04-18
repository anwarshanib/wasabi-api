<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

/**
 * Wraps the Wasabi Card CARDHOLDER API endpoints.
 *
 * Upstream endpoints:
 *   POST /merchant/core/mcb/card/holder/occupations       (Cardholder Occupation list)
 *   POST /merchant/core/mcb/card/holder/create            (Create Cardholder — Deprecated)
 *   POST /merchant/core/mcb/card/holder/update            (Update Cardholder — Deprecated)
 *   POST /merchant/core/mcb/card/holder/v2/create         (Create Cardholder V2)
 *   POST /merchant/core/mcb/card/holder/v2/update         (Update Cardholder V2)
 *   POST /merchant/core/mcb/card/holder/query             (Cardholder List)
 *   POST /merchant/core/mcb/card/holder/updateEmail       (Update Cardholder Email)
 */
final class CardholderService
{
    public function __construct(
        private readonly WasabiCardClient $client,
    ) {}

    /**
     * Return all supported cardholder occupation codes.
     *
     * @return array<int, array{
     *     occupationCode: string,
     *     description: string,
     * }>
     */
    public function occupations(): array
    {
        return $this->client->post('/merchant/core/mcb/card/holder/occupations')['data'] ?? [];
    }

    /**
     * Create a new cardholder — Deprecated V1 endpoint.
     *
     * @param  array{
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     areaCode: string,
     *     mobile: string,
     *     email: string,
     *     firstName: string,
     *     lastName: string,
     *     birthday: string,
     *     nationality: string,
     *     country: string,
     *     town: string,
     *     address: string,
     *     postCode: string,
     * } $params
     *
     * @return array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     statusFlowLocation: string,
     *     status: string,
     *     description: string,
     *     respMsg: string,
     * }
     */
    public function createCardholderDeprecated(array $params): array
    {
        $body = [
            'merchantOrderNo' => $params['merchantOrderNo'],
            'cardTypeId'      => (int) $params['cardTypeId'],
            'areaCode'        => $params['areaCode'],
            'mobile'          => $params['mobile'],
            'email'           => $params['email'],
            'firstName'       => $params['firstName'],
            'lastName'        => $params['lastName'],
            'birthday'        => $params['birthday'],
            'nationality'     => $params['nationality'],
            'country'         => $params['country'],
            'town'            => $params['town'],
            'address'         => $params['address'],
            'postCode'        => $params['postCode'],
        ];

        return $this->client->post('/merchant/core/mcb/card/holder/create', $body)['data'] ?? [];
    }

    /**
     * Update an existing cardholder — Deprecated V1 endpoint.
     *
     * @param  array{
     *     holderId: int,
     *     areaCode: string,
     *     mobile: string,
     *     email: string,
     *     firstName: string,
     *     lastName: string,
     *     birthday: string,
     *     nationality: string,
     *     country: string,
     *     town: string,
     *     address: string,
     *     postCode: string,
     * } $params
     *
     * @return array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     statusFlowLocation: string,
     *     status: string,
     *     description: string,
     *     respMsg: string,
     * }
     */
    public function updateCardholderDeprecated(array $params): array
    {
        $body = [
            'holderId'    => (int) $params['holderId'],
            'areaCode'    => $params['areaCode'],
            'mobile'      => $params['mobile'],
            'email'       => $params['email'],
            'firstName'   => $params['firstName'],
            'lastName'    => $params['lastName'],
            'birthday'    => $params['birthday'],
            'nationality' => $params['nationality'],
            'country'     => $params['country'],
            'town'        => $params['town'],
            'address'     => $params['address'],
            'postCode'    => $params['postCode'],
        ];

        return $this->client->post('/merchant/core/mcb/card/holder/update', $body)['data'] ?? [];
    }

    /**
     * Create a new cardholder — V2 endpoint.
     *
     * Supports two models:
     *   B2B: Standard fields only (no KYC documents required).
     *   B2C: All B2B fields plus gender, occupation, annualSalary, accountPurpose,
     *        expectedMonthlyVolume, idType, idNumber, issueDate, idNoExpiryDate,
     *        idFrontId, idBackId, idHoldId, ipAddress, and optional kycVerification.
     *
     * This endpoint supports a webhook notification on status change.
     *
     * @param  array<string, mixed> $params
     * @return array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     statusFlowLocation: string,
     *     status: string,
     *     description: string|null,
     *     respMsg: string|null,
     * }
     */
    public function createCardholderV2(array $params): array
    {
        $body = [
            'cardHolderModel' => $params['cardHolderModel'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'cardTypeId'      => (int) $params['cardTypeId'],
            'areaCode'        => $params['areaCode'],
            'mobile'          => $params['mobile'],
            'email'           => $params['email'],
            'firstName'       => $params['firstName'],
            'lastName'        => $params['lastName'],
            'birthday'        => $params['birthday'],
            'country'         => $params['country'],
            'town'            => $params['town'],
            'address'         => $params['address'],
            'postCode'        => $params['postCode'],
        ];

        // B2C-only fields
        $b2cFields = [
            'nationality', 'gender', 'occupation', 'annualSalary', 'accountPurpose',
            'expectedMonthlyVolume', 'idType', 'idNumber', 'issueDate', 'idNoExpiryDate',
            'idFrontId', 'idBackId', 'idHoldId', 'ipAddress',
        ];

        foreach ($b2cFields as $field) {
            if (! empty($params[$field])) {
                $body[$field] = $params[$field];
            }
        }

        if (! empty($params['kycVerification'])) {
            $body['kycVerification'] = $params['kycVerification'];
        }

        return $this->client->post('/merchant/core/mcb/card/holder/v2/create', $body)['data'] ?? [];
    }

    /**
     * Update an existing cardholder — V2 endpoint.
     *
     * Updating is supported only when status=reject.
     * Supports two models: B2B and B2C (same field sets as createCardholderV2,
     * but uses holderId instead of merchantOrderNo + cardTypeId).
     *
     * This endpoint supports a webhook notification on status change.
     *
     * @param  array<string, mixed> $params
     * @return array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     cardTypeId: int,
     *     statusFlowLocation: string,
     *     status: string,
     *     description: string|null,
     *     respMsg: string|null,
     * }
     */
    public function updateCardholderV2(array $params): array
    {
        $body = [
            'cardHolderModel' => $params['cardHolderModel'],
            'holderId'        => (int) $params['holderId'],
            'areaCode'        => $params['areaCode'],
            'mobile'          => $params['mobile'],
            'email'           => $params['email'],
            'firstName'       => $params['firstName'],
            'lastName'        => $params['lastName'],
            'birthday'        => $params['birthday'],
            'country'         => $params['country'],
            'town'            => $params['town'],
            'address'         => $params['address'],
            'postCode'        => $params['postCode'],
        ];

        // B2C-only fields
        $b2cFields = [
            'nationality', 'gender', 'occupation', 'annualSalary', 'accountPurpose',
            'expectedMonthlyVolume', 'idType', 'idNumber', 'issueDate', 'idNoExpiryDate',
            'idFrontId', 'idBackId', 'idHoldId', 'ipAddress',
        ];

        foreach ($b2cFields as $field) {
            if (! empty($params[$field])) {
                $body[$field] = $params[$field];
            }
        }

        if (! empty($params['kycVerification'])) {
            $body['kycVerification'] = $params['kycVerification'];
        }

        return $this->client->post('/merchant/core/mcb/card/holder/v2/update', $body)['data'] ?? [];
    }

    /**
     * Return a paginated list of cardholders.
     *
     * Note: areaCode and mobile must be passed together or not at all.
     *
     * @param  array{
     *     pageNum: int,
     *     pageSize: int,
     *     holderId?: int|null,
     *     areaCode?: string|null,
     *     mobile?: string|null,
     *     email?: string|null,
     *     merchantOrderNo?: string|null,
     * } $params
     *
     * @return array{
     *     total: int,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function cardholderList(array $params): array
    {
        $body = [
            'pageNum'  => (int) $params['pageNum'],
            'pageSize' => (int) $params['pageSize'],
        ];

        if (! empty($params['holderId'])) {
            $body['holderId'] = (int) $params['holderId'];
        }

        if (! empty($params['areaCode'])) {
            $body['areaCode'] = $params['areaCode'];
        }

        if (! empty($params['mobile'])) {
            $body['mobile'] = $params['mobile'];
        }

        if (! empty($params['email'])) {
            $body['email'] = $params['email'];
        }

        if (! empty($params['merchantOrderNo'])) {
            $body['merchantOrderNo'] = $params['merchantOrderNo'];
        }

        return $this->client->post('/merchant/core/mcb/card/holder/query', $body)['data'] ?? [];
    }

    /**
     * Update the email address of a cardholder.
     *
     * Updating is supported only when cardholder status=pass_audit.
     * This endpoint supports a webhook notification on status change.
     *
     * @param  array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     email: string,
     * } $params
     *
     * @return array{
     *     holderId: int,
     *     merchantOrderNo: string,
     *     orderNo: string,
     *     status: string,
     *     description: string|null,
     *     remark: string|null,
     * }
     */
    public function updateCardholderEmail(array $params): array
    {
        $body = [
            'holderId'        => (int) $params['holderId'],
            'merchantOrderNo' => $params['merchantOrderNo'],
            'email'           => $params['email'],
        ];

        return $this->client->post('/merchant/core/mcb/card/holder/updateEmail', $body)['data'] ?? [];
    }
}
