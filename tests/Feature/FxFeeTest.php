<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ClientBalance;
use App\Models\ClientTransaction;
use App\Models\FeeLedger;
use App\Models\FeeSetting;
use App\Models\PlatformSetting;
use App\Models\TenantResource;
use App\Services\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FxFeeTest extends TestCase
{
    use RefreshDatabase;

    private string $rawToken;
    private ApiToken $apiToken;

    // Reusable webhook payload builder
    private function crossBorderPayload(array $overrides = []): array
    {
        return array_merge([
            'type'             => 'card_patch_cross_border',
            'status'           => 'success',
            'tradeNo'          => 'TRADE_FX_001',
            'cardNo'           => 'CARD_FX_001',
            'amount'           => '1.50',   // Wasabi's cross-border fee charged to merchant
            'authorizedAmount' => '100.00', // original USD-equivalent transaction amount
        ], $overrides);
    }

    private function crossBorderHeaders(string $requestId = 'REQ_FX_001'): array
    {
        return [
            'X-WSB-CATEGORY'   => 'card_fee_patch',
            'X-WSB-REQUEST-ID' => $requestId,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        [$this->rawToken, $this->apiToken] = ApiToken::generateAndCreate(
            'FX Test Client', 'fx@example.com', 'FX fee test token'
        );

        PlatformSetting::set(PlatformSetting::KEY_FEE_SOURCE,      '111111111111111111');
        PlatformSetting::set(PlatformSetting::KEY_FEE_DESTINATION, '999999999999999999');

        // Give the client a balance and register a card for ownership resolution
        ClientBalance::forToken($this->apiToken->id);
        ClientBalance::where('api_token_id', $this->apiToken->id)
            ->update(['balance' => '500.0000']);

        TenantResource::create([
            'resource_type' => TenantResource::TYPE_CARD,
            'wasabi_id'     => 'CARD_FX_001',
            'api_token_id'  => $this->apiToken->id,
        ]);

        // Pre-debit the cross-border fee so debitAuthFee() has a balance to deduct from
        // (avoids "insufficient balance" noise in these tests — we test fee collection only)
    }

    // -------------------------------------------------------------------------
    // 1. Fee IS collected on cross-border webhook (active, rate > 0)
    // -------------------------------------------------------------------------

    public function test_fx_fee_is_collected_on_cross_border_webhook(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        // Mock both services: ClientBalanceService to avoid DB debit, FeeService to assert call
        $this->mock(\App\Services\ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('debitAuthFee')->once();
        });

        $this->mock(FeeService::class, function ($mock) {
            $mock->shouldReceive('collectFxFee')
                 ->once()
                 ->with($this->apiToken->id, 'TRADE_FX_001', 100.0);
        });

        $response = $this->postJson(
            '/api/webhook',
            $this->crossBorderPayload(),
            $this->crossBorderHeaders()
        );

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // 2. Fee is NOT collected for plain auth fee patches (not cross-border)
    // -------------------------------------------------------------------------

    public function test_fx_fee_not_collected_for_auth_fee_patch(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        // authorizedAmount present but type is NOT card_patch_cross_border
        $response = $this->postJson('/api/webhook', $this->crossBorderPayload([
            'type'    => 'card_patch_auth',
            'tradeNo' => 'TRADE_AUTH_001',
        ]), $this->crossBorderHeaders('REQ_AUTH_001'));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // 3. Fee is NOT collected when authorizedAmount is 0 / missing
    // -------------------------------------------------------------------------

    public function test_fx_fee_not_collected_when_authorized_amount_is_zero(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        $response = $this->postJson('/api/webhook', $this->crossBorderPayload([
            'authorizedAmount' => '0',
            'tradeNo'          => 'TRADE_FX_ZERO',
        ]), $this->crossBorderHeaders('REQ_FX_ZERO'));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // 4. Fee is NOT collected when FX setting is inactive
    // -------------------------------------------------------------------------

    public function test_fx_fee_not_collected_when_setting_is_inactive(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => false],
        );

        $response = $this->postJson(
            '/api/webhook',
            $this->crossBorderPayload(['tradeNo' => 'TRADE_FX_INACTIVE']),
            $this->crossBorderHeaders('REQ_FX_INACTIVE')
        );

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // 5. Fee is NOT collected when rate is zero
    // -------------------------------------------------------------------------

    public function test_fx_fee_not_collected_when_rate_is_zero(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 0, 'is_active' => true],
        );

        $response = $this->postJson(
            '/api/webhook',
            $this->crossBorderPayload(['tradeNo' => 'TRADE_FX_RATE0']),
            $this->crossBorderHeaders('REQ_FX_RATE0')
        );

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // -------------------------------------------------------------------------
    // 6. fee_ledger row has correct values
    // -------------------------------------------------------------------------

    public function test_fx_fee_ledger_row_created_with_correct_values(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        $this->mock(\App\Services\WasabiCard\AccountService::class, function ($mock) {
            $mock->shouldReceive('fundTransfer')->once()->andReturn(true);
        });

        $this->mock(\App\Services\ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('debitAuthFee')->once();
        });

        $this->postJson('/api/webhook', $this->crossBorderPayload([
            'tradeNo'          => 'TRADE_FX_LEDGER',
            'authorizedAmount' => '200.00',
        ]), $this->crossBorderHeaders('REQ_FX_LEDGER'));

        // 1.50% of $200 = $3.00
        $ledger = FeeLedger::first();
        $this->assertNotNull($ledger);
        $this->assertSame(FeeSetting::TYPE_FX, $ledger->fee_type);
        $this->assertEquals(200.0, (float) $ledger->base_amount);
        $this->assertEquals(3.0,   (float) $ledger->fee_amount);    // 1.5% of 200
        $this->assertSame(FeeLedger::STATUS_TRANSFERRED, $ledger->status);
        $this->assertSame('TRADE_FX_LEDGER', $ledger->reference_id);
        $this->assertSame($this->apiToken->id, $ledger->api_token_id);
        $this->assertNotNull($ledger->transferred_at);
        $this->assertStringStartsWith('FEE_FX_', $ledger->wasabi_order_no);
    }

    // -------------------------------------------------------------------------
    // 7. Fee failure does NOT disrupt the webhook response
    // -------------------------------------------------------------------------

    public function test_fx_fee_failure_does_not_disrupt_webhook(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        $this->mock(\App\Services\WasabiCard\AccountService::class, function ($mock) {
            $mock->shouldReceive('fundTransfer')
                 ->once()
                 ->andThrow(new \RuntimeException('Wasabi API unavailable'));
        });

        $this->mock(\App\Services\ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('debitAuthFee')->once();
        });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $response = $this->postJson(
            '/api/webhook',
            $this->crossBorderPayload(['tradeNo' => 'TRADE_FX_FAIL']),
            $this->crossBorderHeaders('REQ_FX_FAIL')
        );

        // Webhook must still return 200 — fee failure is internal
        $response->assertStatus(200)->assertJson(['success' => true]);

        // Ledger row written with status=failed
        $ledger = FeeLedger::first();
        $this->assertNotNull($ledger);
        $this->assertSame(FeeLedger::STATUS_FAILED, $ledger->status);
    }

    // -------------------------------------------------------------------------
    // 8. Fee fails gracefully when platform accounts are not configured
    // -------------------------------------------------------------------------

    public function test_fx_fee_fails_gracefully_when_accounts_not_configured(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        // Remove the platform account config
        PlatformSetting::set(PlatformSetting::KEY_FEE_SOURCE,      '');
        PlatformSetting::set(PlatformSetting::KEY_FEE_DESTINATION, '');

        $this->mock(\App\Services\ClientBalanceService::class, function ($mock) {
            $mock->shouldReceive('debitAuthFee')->once();
        });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $response = $this->postJson(
            '/api/webhook',
            $this->crossBorderPayload(['tradeNo' => 'TRADE_FX_NOCFG']),
            $this->crossBorderHeaders('REQ_FX_NOCFG')
        );

        $response->assertStatus(200)->assertJson(['success' => true]);

        $ledger = FeeLedger::first();
        $this->assertNotNull($ledger);
        $this->assertSame(FeeLedger::STATUS_FAILED, $ledger->status);
        $this->assertNull($ledger->wasabi_order_no);
    }

    // -------------------------------------------------------------------------
    // 9. Fee is NOT collected when webhook status is not success
    // -------------------------------------------------------------------------

    public function test_fx_fee_not_collected_on_non_success_status(): void
    {
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_FX],
            ['rate' => 1.50, 'is_active' => true],
        );

        // card_fee_patch with status != success should be ignored entirely
        $response = $this->postJson('/api/webhook', $this->crossBorderPayload([
            'status'  => 'fail',
            'tradeNo' => 'TRADE_FX_FAIL_STATUS',
        ]), $this->crossBorderHeaders('REQ_FX_FAIL_STATUS'));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('fee_ledger', 0);
    }
}
