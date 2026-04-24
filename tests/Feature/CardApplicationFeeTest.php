<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ClientBalance;
use App\Models\FeeLedger;
use App\Models\FeeSetting;
use App\Models\PlatformSetting;
use App\Services\ClientBalanceService;
use App\Services\FeeService;
use App\Services\WasabiCard\CardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CardApplicationFeeTest extends TestCase
{
    use RefreshDatabase;

    private string $rawToken;
    private ApiToken $apiToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an active API token for all requests
        [$this->rawToken, $this->apiToken] = ApiToken::generateAndCreate(
            'Test Client', 'test@example.com', 'Fee test token'
        );

        // Seed platform settings (source + destination account)
        PlatformSetting::set(PlatformSetting::KEY_FEE_SOURCE,      '111111111111111111');
        PlatformSetting::set(PlatformSetting::KEY_FEE_DESTINATION, '999999999999999999');

        // Give the client a $200 balance so balance checks pass
        ClientBalance::forToken($this->apiToken->id);
        ClientBalance::where('api_token_id', $this->apiToken->id)
            ->update(['balance' => '200.0000']);
    }

    // -------------------------------------------------------------------------
    // Validation: accountId is now optional (nullable) for PREPAID cards
    // -------------------------------------------------------------------------

    public function test_create_card_v2_works_without_account_id(): void
    {
        $this->mock(CardService::class, function ($mock) {
            $mock->shouldReceive('getCardTypeById')->andReturn(null);
            $mock->shouldReceive('createCardV2')->once()->andReturn([
                'orderNo'         => 'WSB_ORDER_OPT',
                'cardNo'          => 'CARD_OPT',
                'status'          => 'success',
                'amount'          => 20,
                'fee'             => 0,
                'receivedAmount'  => 20,
                'merchantOrderNo' => 'ORDER20260422000001',
            ]);
        });

        $this->mock(ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('reserveCardCreate')->once()->andReturn(['debitId' => 1, 'feeId' => null]);
            $mock->shouldReceive('confirmPending')->zeroOrMoreTimes();
        });

        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => 'ORDER20260422000001',
            'cardTypeId'      => 1,
            'amount'          => 20,
            // accountId intentionally omitted — should work for PREPAID cards
        ], ['X-API-KEY' => $this->rawToken]);

        $response->assertStatus(200);
    }

    public function test_create_card_deprecated_works_without_account_id(): void
    {
        $this->mock(CardService::class, function ($mock) {
            $mock->shouldReceive('getCardTypeById')->andReturn(null);
            $mock->shouldReceive('createCardDeprecated')->once()->andReturn([
                'orderNo'         => 'WSB_ORDER_OPT2',
                'cardNo'          => 'CARD_OPT2',
                'status'          => 'success',
                'amount'          => 20,
                'fee'             => 0,
                'receivedAmount'  => 20,
                'merchantOrderNo' => 'ORDER20260422000002',
            ]);
        });

        $this->mock(ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('reserveCardCreate')->once()->andReturn(['debitId' => 1, 'feeId' => null]);
            $mock->shouldReceive('confirmPending')->zeroOrMoreTimes();
        });

        $response = $this->postJson('/api/v1/cards/create', [
            'merchantOrderNo' => 'ORDER20260422000002',
            'cardTypeId'      => 1,
            // accountId intentionally omitted — nullable now
        ], ['X-API-KEY' => $this->rawToken]);

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Balance check rejects card creation when client has insufficient funds
    // -------------------------------------------------------------------------

    public function test_card_creation_rejected_when_insufficient_balance(): void
    {
        // Zero out the balance
        ClientBalance::where('api_token_id', $this->apiToken->id)->update(['balance' => '0.0000']);

        // CardService should NOT be called (createCardV2), but getCardTypeById IS called before balance check
        $this->mock(CardService::class, function ($mock) {
            $mock->shouldReceive('getCardTypeById')->once()->andReturn(null);
            $mock->shouldNotReceive('createCardV2');
        });

        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => 'ORDER20260422000003',
            'cardTypeId'      => 1,
            'amount'          => 100,
        ], ['X-API-KEY' => $this->rawToken]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Fee fires when card is created successfully (active fee, rate > 0)
    // -------------------------------------------------------------------------

    public function test_card_application_fee_is_collected_on_successful_v2_card_creation(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 5.00, 'is_active' => true],
        );

        $wasabiOrderNo   = 'WSB_ORDER_001';
        $merchantOrderNo = 'ORDER20260422000004';

        // Register resource so the webhook can resolve api_token_id
        \App\Models\TenantResource::create([
            'resource_type'    => \App\Models\TenantResource::TYPE_ORDER,
            'wasabi_id'        => $wasabiOrderNo,
            'api_token_id'     => $this->apiToken->id,
            'merchant_order_no'=> $merchantOrderNo,
        ]);

        // Mock FeeService to verify it is called with the correct arguments
        $this->mock(FeeService::class, function ($mock) use ($wasabiOrderNo) {
            $mock->shouldReceive('collectCardApplicationFee')
                 ->once()
                 ->with($this->apiToken->id, $wasabiOrderNo);
        });

        // Fire the card_transaction/create/success webhook (Wasabi sends this when card is issued)
        $response = $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
            'cardNo'          => 'CARD_001',
            'amount'          => '100',
            'fee'             => '2.5',
            'receivedAmount'  => '97.5',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_FEE_TEST_001',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Fee ledger is written via real FeeService (no CardService mock)
    // -------------------------------------------------------------------------

    public function test_fee_ledger_row_created_with_correct_values(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 5.00, 'is_active' => true],
        );

        $wasabiOrderNo   = 'WSB_ORDER_002';
        $merchantOrderNo = 'ORDER20260422000005';

        \App\Models\TenantResource::create([
            'resource_type'    => \App\Models\TenantResource::TYPE_ORDER,
            'wasabi_id'        => $wasabiOrderNo,
            'api_token_id'     => $this->apiToken->id,
            'merchant_order_no'=> $merchantOrderNo,
        ]);

        // Mock AccountService to avoid real Wasabi HTTP call
        $this->mock(\App\Services\WasabiCard\AccountService::class, function ($mock) {
            $mock->shouldReceive('fundTransfer')->once()->andReturn(true);
        });

        // Fire confirmation webhook — real FeeService runs, AccountService is mocked
        $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
            'cardNo'          => 'CARD_002',
            'amount'          => '100',
            'fee'             => '2.5',
            'receivedAmount'  => '97.5',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_FEE_LEDGER_001',
        ]);

        $ledger = FeeLedger::first();
        $this->assertNotNull($ledger);
        $this->assertSame(FeeSetting::TYPE_CARD_APPLICATION, $ledger->fee_type);
        $this->assertEquals(5.0000, (float) $ledger->fee_amount);
        $this->assertEquals(5.0000, (float) $ledger->base_amount);
        $this->assertSame(FeeLedger::STATUS_TRANSFERRED, $ledger->status);
        $this->assertSame($wasabiOrderNo, $ledger->reference_id);
        $this->assertNotNull($ledger->transferred_at);
        $this->assertStringStartsWith('FEE_CARD_APPLICATION_', $ledger->wasabi_order_no);
    }

    // -------------------------------------------------------------------------
    // Fee is skipped when fee setting is inactive
    // -------------------------------------------------------------------------

    public function test_no_fee_ledger_when_card_application_fee_is_inactive(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 5.00, 'is_active' => false], // inactive
        );

        \App\Models\TenantResource::create([
            'resource_type'     => \App\Models\TenantResource::TYPE_ORDER,
            'wasabi_id'         => 'WSB_ORDER_003',
            'api_token_id'      => $this->apiToken->id,
            'merchant_order_no' => 'ORDER20260422000006',
        ]);

        $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => 'WSB_ORDER_003',
            'merchantOrderNo' => 'ORDER20260422000006',
            'cardNo'          => 'CARD_003',
            'amount'          => '100',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_INACTIVE_FEE_001',
        ]);

        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // Fee is skipped when rate is 0
    // -------------------------------------------------------------------------

    public function test_no_fee_ledger_when_rate_is_zero(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 0, 'is_active' => true],
        );

        \App\Models\TenantResource::create([
            'resource_type'     => \App\Models\TenantResource::TYPE_ORDER,
            'wasabi_id'         => 'WSB_ORDER_004',
            'api_token_id'      => $this->apiToken->id,
            'merchant_order_no' => 'ORDER20260422000007',
        ]);

        $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => 'WSB_ORDER_004',
            'merchantOrderNo' => 'ORDER20260422000007',
            'cardNo'          => 'CARD_004',
            'amount'          => '100',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_ZERO_FEE_001',
        ]);

        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // Fee failure never causes card creation to fail
    // -------------------------------------------------------------------------

    public function test_card_creation_succeeds_even_if_fee_transfer_fails(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 5.00, 'is_active' => true],
        );

        $wasabiOrderNo   = 'WSB_ORDER_005';
        $merchantOrderNo = 'ORDER20260422000008';

        \App\Models\TenantResource::create([
            'resource_type'     => \App\Models\TenantResource::TYPE_ORDER,
            'wasabi_id'         => $wasabiOrderNo,
            'api_token_id'      => $this->apiToken->id,
            'merchant_order_no' => $merchantOrderNo,
        ]);

        // AccountService throws — simulates Wasabi fundTransfer failure
        $this->mock(\App\Services\WasabiCard\AccountService::class, function ($mock) {
            $mock->shouldReceive('fundTransfer')
                 ->once()
                 ->andThrow(new \RuntimeException('Wasabi API error'));
        });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        // Fire confirmation webhook — FeeService runs, AccountService throws
        $response = $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
            'cardNo'          => 'CARD_005',
            'amount'          => '100',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_FEE_FAIL_001',
        ]);

        // Webhook always returns 200 to Wasabi — fee failure must not break the ack
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Ledger row recorded as failed
        $ledger = FeeLedger::first();
        $this->assertNotNull($ledger);
        $this->assertSame(FeeLedger::STATUS_FAILED, $ledger->status);
    }

    // -------------------------------------------------------------------------
    // Unauthenticated request is rejected
    // -------------------------------------------------------------------------

    public function test_card_creation_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => 'ORDER20260422000009',
            'cardTypeId'      => 1,
            'amount'          => 100,
        ]); // No X-API-KEY header

        $response->assertStatus(401);
    }
}

