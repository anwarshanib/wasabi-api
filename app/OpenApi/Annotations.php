<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Wasabi Card API',
    version: '1.0.0',
    description: "Backend integration layer for the Wasabi Card Open API platform.\n\n## Authentication\nAll endpoints require an **X-API-KEY** header.\n\n## Response Envelope\nEvery response uses: `{ success, code, msg, data }`\n\n## Rate Limiting\n60 requests per minute per API key.",
    contact: new OA\Contact(email: 'support@example.com')
)]
#[OA\Server(url: '/', description: 'Current environment')]
#[OA\SecurityScheme(
    securityScheme: 'ApiKeyAuth',
    type: 'apiKey',
    name: 'X-API-KEY',
    in: 'header',
    description: 'API key issued to third-party developers.'
)]
#[OA\Tag(name: 'Common',      description: 'Reference data — regions, cities, mobile codes, file upload')]
#[OA\Tag(name: 'Work Orders', description: 'Work order management — submit and query Wasabi platform work orders')]
#[OA\Tag(name: 'Account',     description: 'Account management — assets and account list')]
#[OA\Tag(name: 'Wallet',      description: 'Wallet management — deposit orders and transaction history (Deprecated endpoints)')]
#[OA\Tag(name: 'Card',        description: 'Card management — card types, create and manage cards')]
#[OA\Schema(
    schema: 'AccountObject',
    description: 'A single merchant account with live balance information',
    properties: [
        new OA\Property(property: 'accountId',        type: 'string',  example: '19847563867367666', description: 'Unique account identifier'),
        new OA\Property(property: 'accountName',      type: 'string',  example: 'wallet9023',        description: 'Account display name'),
        new OA\Property(property: 'accountType',      type: 'string',  example: 'WALLET',            description: 'Account type (e.g. WALLET, MARGIN)'),
        new OA\Property(property: 'currency',         type: 'string',  example: 'USD',               description: 'ISO 4217 currency code'),
        new OA\Property(property: 'totalBalance',     type: 'number',  example: 100,                 description: 'Total balance including frozen funds'),
        new OA\Property(property: 'availableBalance', type: 'number',  example: 100,                 description: 'Spendable balance'),
        new OA\Property(property: 'frozenBalance',    type: 'number',  example: 0,                   description: 'Frozen / reserved balance'),
        new OA\Property(property: 'digital',          type: 'integer', example: 2,                   description: 'Number of decimal places for the currency'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'code',    type: 'integer', example: 401),
        new OA\Property(property: 'msg',     type: 'string',  example: 'Invalid or missing API key.'),
        new OA\Property(property: 'data',    nullable: true,  example: null),
    ],
    type: 'object'
)]
final class Annotations {}

