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
use App\Services\WasabiCard\AccountService;
use App\Services\WasabiCard\CardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-To-End HTTP Integration Test — Full Third-Party Balance Flow.
 *
 * Calls the actual API endpoints exactly as a real third-party client would,
 * verifying the client ledger (ClientTransaction + ClientBalance) and the
 * platform margin account (FeeLedger) after every operation.
 *
 * Only external Wasabi HTTP calls are mocked (CardService, AccountService).
 * All platform services (ClientBalanceService, FeeService, TenantOwnershipService,
 * WebhookController, CardController) run with real logic.
 *
 * ┌─ Test flow ────────────────────────────────────────────────────────────────┐
 * │  STEP 1  Create third-party client (API token + fee config)               │
 * │  STEP 2  POST /api/webhook  (wallet_transaction_v2 deposit success)       │
 * │  STEP 3  POST /api/v1/cards/create-v2  (reserve balance, pending rows)   │
 * │  STEP 4  POST /api/webhook  (card_transaction create success confirm)     │
 * │  STEP 5  Ledger & margin-account integrity assertions                     │
 * └────────────────────────────────────────────────────────────────────────────┘
 *
 * Fee scenario:
 *   Deposit fee        : 2% of received amount
 *   Card-create fee    : $5 flat (platform margin)
 *   Wasabi BIN fee     : $5 (cardPrice on card type)
 *   Wasabi proc fee    : 1% of card amount (rechargeFeeRate)
 */
class EndToEndBalanceFlowTest extends TestCase
{
    use RefreshDatabase;

    // ─── Shared state set up before each test ────────────────────────────────

    private string  $rawToken;
    private ApiToken $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Fee structure
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_DEPOSIT],
            ['rate' => 2.0, 'is_active' => true],
        );
        FeeSetting::updateOrCreate(
            ['fee_type' => FeeSetting::TYPE_CARD_APPLICATION],
            ['rate' => 5.0, 'is_active' => true],
        );

        // Platform margin accounts for fund transfers
        PlatformSetting::set(PlatformSetting::KEY_FEE_SOURCE,      'PLATFORM_WALLET_SRC_001');
        PlatformSetting::set(PlatformSetting::KEY_FEE_DESTINATION, 'PLATFORM_WALLET_DST_001');

        // Prevent real Wasabi fund-transfer HTTP calls in FeeService
        $this->mock(AccountService::class, function ($mock): void {
            $mock->shouldReceive('fundTransfer')->andReturn(true);
        });

        // Create the third-party client
        [$this->rawToken, $this->client] = ApiToken::generateAndCreate(
            'Acme Corp',
            'acme@example.com',
            'E2E integration test client',
        );

        // Ensure balance row exists at $0.00
        ClientBalance::forToken($this->client->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1 — Verify initial state
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The newly created client must have a $0 balance and an empty ledger.
     */
    public function test_step1_initial_state(): void
    {
        $this->assertDatabaseHas('api_tokens', [
            'name'  => 'Acme Corp',
            'email' => 'acme@example.com',
        ]);

        $this->assertDatabaseHas('client_balances', [
            'api_token_id' => $this->client->id,
            'balance'      => '0.0000',
        ]);

        $this->assertDatabaseCount('client_transactions', 0);
        $this->assertDatabaseCount('fee_ledger', 0);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2 — Deposit webhook
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/webhook
     *
     * Scenario:
     *   Client sends 500 USDT.
     *   Wasabi chain fee = $10 → receivedAmount = $490 USD.
     *   Platform deposit fee = 2% of $490 = $9.80.
     *   Net credited to client = $490 − $9.80 = $480.20.
     *
     * Expected client ledger after this call:
     *   ┌──────────────┬────────┬──────────┬───────────┐
     *   │ event        │ type   │ amount   │ status    │
     *   ├──────────────┼────────┼──────────┼───────────┤
     *   │ deposit      │ credit │ $480.20  │ confirmed │
     *   │ platform_fee │ debit  │   $9.80  │ confirmed │ ← informational; fee baked into net credit
     *   └──────────────┴────────┴──────────┴───────────┘
     *   Current balance: $480.20
     *
     * Expected margin account (fee_ledger) after this call:
     *   ┌──────────────┬──────────┬─────────────┐
     *   │ fee_type     │ amount   │ status      │
     *   ├──────────────┼──────────┼─────────────┤
     *   │ deposit      │   $9.80  │ transferred │
     *   └──────────────┴──────────┴─────────────┘
     */
    public function test_step2_deposit_webhook_credits_client_balance(): void
    {
        $depositOrderNo = 'WSB_WALLET_DEPOSIT_001';

        // The webhook resolves ownership via TenantResource (TYPE_ORDER orderNo → api_token_id).
        // In production this row is created when the client calls the wallet-address API.
        TenantResource::create([
            'api_token_id'  => $this->client->id,
            'resource_type' => TenantResource::TYPE_ORDER,
            'wasabi_id'     => $depositOrderNo,
        ]);

        // ── Call the endpoint ────────────────────────────────────────────────
        $response = $this->postJson('/api/webhook', [
            'type'           => 'deposit',
            'status'         => 'success',
            'orderNo'        => $depositOrderNo,
            'receivedAmount' => 490.00,
            'amount'         => 500.00,
            'fee'            => 10.00,
        ], [
            'X-WSB-CATEGORY'   => 'wallet_transaction_v2',
            'X-WSB-REQUEST-ID' => 'REQ_DEPOSIT_001',
        ]);

        // ── Wasabi protocol: always returns 200 with Wasabi ack ──────────────
        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'code'    => 200,
                'msg'     => null,
                'data'    => null,
            ]);

        // ── Client balance ───────────────────────────────────────────────────
        $balance = ClientBalance::where('api_token_id', $this->client->id)->first();
        $this->assertEquals(480.20, round((float) $balance->balance, 2), 'Balance should be $480.20 after deposit');

        // ── Client ledger: deposit credit row ────────────────────────────────
        $this->assertDatabaseHas('client_transactions', [
            'api_token_id' => $this->client->id,
            'event'        => ClientTransaction::EVENT_DEPOSIT,
            'type'         => 'credit',
            'amount'       => '480.2000',
            'status'       => ClientTransaction::STATUS_CONFIRMED,
            'reference_id' => $depositOrderNo,
        ]);

        // ── Client ledger: platform_fee informational row ────────────────────
        // balance_before = balance_after because fee was already excluded from net credit.
        $feeTx = ClientTransaction::where('api_token_id', $this->client->id)
            ->where('event', ClientTransaction::EVENT_PLATFORM_FEE)
            ->where('reference_id', $depositOrderNo)
            ->first();

        $this->assertNotNull($feeTx, 'platform_fee transaction row should exist');
        $this->assertEquals(9.80,  round((float) $feeTx->amount, 2));
        $this->assertEquals('debit', $feeTx->type);
        $this->assertEquals($feeTx->balance_before, $feeTx->balance_after, 'Deposit fee row must be informational (no double-deduct)');

        // 2 total ledger rows after deposit
        $this->assertDatabaseCount('client_transactions', 2);

        // ── Platform margin account (fee_ledger) ──────────────────────────────
        $depositFeeRow = FeeLedger::where('api_token_id', $this->client->id)
            ->where('fee_type', FeeSetting::TYPE_DEPOSIT)
            ->first();

        $this->assertNotNull($depositFeeRow, 'fee_ledger row should exist for deposit fee');
        $this->assertEquals(9.80,                    round($depositFeeRow->fee_amount, 2));
        $this->assertEquals(FeeLedger::STATUS_TRANSFERRED, $depositFeeRow->status);
        $this->assertEquals($depositOrderNo,         $depositFeeRow->reference_id);
        $this->assertNotNull($depositFeeRow->transferred_at);

        // 1 total fee_ledger rows after deposit
        $this->assertDatabaseCount('fee_ledger', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3 — Card creation request
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/cards/create-v2
     *
     * Scenario:
     *   Card amount: $50.
     *   Card type 111002:
     *     • cardPrice        = $5.00  (Wasabi BIN/issuance fee)
     *     • rechargeFeeRate  = 1%     → $50 × 1% = $0.50 (Wasabi processing fee)
     *     • rechargeFixedFee = $0
     *   Platform card-application fee = $5 flat.
     *   Total reserved = $50 + $5 + $0.50 + $5 = $60.50.
     *   Balance after = $480.20 − $60.50 = $419.70.
     *
     *   Wasabi returns status=processing (card not yet issued — async).
     *   All 4 reservation rows start as PENDING.
     *
     * Expected client ledger rows added (all PENDING, reference_id = merchantOrderNo):
     *   ┌────────────────────────┬───────┬─────────┬─────────┐
     *   │ event                  │ type  │ amount  │ status  │
     *   ├────────────────────────┼───────┼─────────┼─────────┤
     *   │ card_create            │ debit │  $50.00 │ pending │
     *   │ wasabi_card_fee        │ debit │   $5.00 │ pending │
     *   │ wasabi_processing_fee  │ debit │   $0.50 │ pending │
     *   │ platform_fee           │ debit │   $5.00 │ pending │
     *   └────────────────────────┴───────┴─────────┴─────────┘
     *   Current balance: $419.70
     *
     * Expected margin account (fee_ledger) rows added:
     *   ┌──────────────────┬──────────┬─────────────┐
     *   │ fee_type         │ amount   │ status      │
     *   ├──────────────────┼──────────┼─────────────┤
     *   │ card_application │   $5.00  │ transferred │
     *   └──────────────────┴──────────┴─────────────┘
     */
    public function test_step3_card_create_reserves_balance(): void
    {
        // Bring balance to $480.20 (replays the deposit)
        $this->runDepositWebhook();

        $merchantOrderNo = 'ORDER_CARD_20260422001';

        $this->mock(CardService::class, function ($mock) use ($merchantOrderNo): void {
            // Return Wasabi card type details (BIN fee + processing fee rates)
            $mock->shouldReceive('getCardTypeById')
                ->once()
                ->with(111002)
                ->andReturn([
                    'cardTypeId'                        => 111002,
                    'cardPrice'                         => '5',    // $5 BIN fee
                    'rechargeFeeRate'                   => 1.0,    // 1% processing
                    'rechargeFixedFee'                  => 0.0,
                    'depositAmountMinQuotaForActiveCard' => '20',
                ]);

            // Simulate Wasabi accepting the order (processing = not yet confirmed)
            $mock->shouldReceive('createCardV2')
                ->once()
                ->andReturn([
                    'orderNo'         => 'WSB_CARD_ORDER_001',
                    'cardNo'          => null,          // null while processing
                    'status'          => 'processing',
                    'amount'          => '50',
                    'fee'             => '5.5',
                    'receivedAmount'  => '44.5',
                    'merchantOrderNo' => $merchantOrderNo,
                ]);
        });

        // ── Call the endpoint ────────────────────────────────────────────────
        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => $merchantOrderNo,
            'cardTypeId'      => 111002,
            'amount'          => 50,
        ], ['X-API-KEY' => $this->rawToken]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // ── Client balance: immediately deducted by $60.50 ──────────────────
        $balance = ClientBalance::where('api_token_id', $this->client->id)->first();
        $this->assertEquals(419.70, round((float) $balance->balance, 2), 'Balance should be $419.70 after card reserve');

        // ── 4 PENDING reservation rows ───────────────────────────────────────
        $expectedRows = [
            [ClientTransaction::EVENT_CARD_CREATE,           '50.0000'],
            [ClientTransaction::EVENT_WASABI_CARD_FEE,        '5.0000'],
            [ClientTransaction::EVENT_WASABI_PROCESSING_FEE,  '0.5000'],
            [ClientTransaction::EVENT_PLATFORM_FEE,           '5.0000'],
        ];

        foreach ($expectedRows as [$event, $expectedAmount]) {
            $this->assertDatabaseHas('client_transactions', [
                'api_token_id' => $this->client->id,
                'event'        => $event,
                'type'         => 'debit',
                'amount'       => $expectedAmount,
                'status'       => ClientTransaction::STATUS_PENDING,
                'reference_id' => $merchantOrderNo,
            ]);
        }

        // 2 from deposit + 4 from card reservation = 6 total
        $this->assertDatabaseCount('client_transactions', 6);

        // ── Platform margin: card_application fee is NOT yet collected ────────
        // Fee is collected only after Wasabi confirms the card was issued (webhook).
        // Only the deposit fee row exists at this point.
        $this->assertDatabaseCount('fee_ledger', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 4 — Wasabi sends card_create confirmation webhook
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/webhook  (card confirmation from Wasabi)
     *
     * Scenario:
     *   Wasabi confirms the card was issued.
     *   All 4 PENDING rows for this merchantOrderNo → CONFIRMED.
     *   Balance does not change (already deducted at reservation time).
     *
     * Expected client ledger rows (all CONFIRMED):
     *   ┌────────────────────────┬───────┬─────────┬───────────┐
     *   │ event                  │ type  │ amount  │ status    │
     *   ├────────────────────────┼───────┼─────────┼───────────┤
     *   │ card_create            │ debit │  $50.00 │ confirmed │
     *   │ wasabi_card_fee        │ debit │   $5.00 │ confirmed │
     *   │ wasabi_processing_fee  │ debit │   $0.50 │ confirmed │
     *   │ platform_fee           │ debit │   $5.00 │ confirmed │
     *   └────────────────────────┴───────┴─────────┴───────────┘
     *   Current balance: $419.70 (unchanged)
     */
    public function test_step4_card_confirm_webhook_flips_pending_to_confirmed(): void
    {
        // Replay deposit + card creation to get pending rows into DB
        $this->runDepositWebhook();
        $merchantOrderNo = 'ORDER_CARD_20260422001';
        $wasabiOrderNo   = 'WSB_CARD_ORDER_001';
        $this->runCardCreateRequest($merchantOrderNo, $wasabiOrderNo);

        // Register the Wasabi orderNo so the webhook can resolve api_token_id
        TenantResource::updateOrCreate(
            ['resource_type' => TenantResource::TYPE_ORDER, 'wasabi_id' => $wasabiOrderNo],
            ['api_token_id' => $this->client->id, 'merchant_order_no' => $merchantOrderNo],
        );

        // ── Call the endpoint ────────────────────────────────────────────────
        $response = $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
            'cardNo'          => 'CARD_ACME_001',
            'amount'          => '50',
            'fee'             => '5.5',
            'receivedAmount'  => '44.5',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_CARD_CONFIRM_001',
        ]);

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'code'    => 200,
                'msg'     => null,
                'data'    => null,
            ]);

        // ── Balance unchanged ────────────────────────────────────────────────
        $balance = ClientBalance::where('api_token_id', $this->client->id)->first();
        $this->assertEquals(419.70, round((float) $balance->balance, 2), 'Balance must stay $419.70 after confirmation');

        // ── All 4 reservation rows now CONFIRMED ─────────────────────────────
        $confirmedRows = ClientTransaction::where('api_token_id', $this->client->id)
            ->where('reference_id', $merchantOrderNo)
            ->where('type', 'debit')
            ->get();

        $this->assertCount(4, $confirmedRows, 'Expected exactly 4 confirmed debit rows for card order');

        foreach ($confirmedRows as $tx) {
            $this->assertEquals(
                ClientTransaction::STATUS_CONFIRMED,
                $tx->status,
                "Row [{$tx->event}] should be confirmed",
            );
        }

        // No pending rows remain
        $this->assertDatabaseMissing('client_transactions', [
            'api_token_id' => $this->client->id,
            'status'       => ClientTransaction::STATUS_PENDING,
        ]);

        // ── Card application fee collected on webhook success ─────────────────
        // Fee is transferred to the platform margin account only after Wasabi
        // confirms the card was issued (not at API call time).
        $cardFeeRow = FeeLedger::where('api_token_id', $this->client->id)
            ->where('fee_type', FeeSetting::TYPE_CARD_APPLICATION)
            ->first();

        $this->assertNotNull($cardFeeRow, 'fee_ledger row should exist after card_transaction/create/success webhook');
        $this->assertEquals(5.00, round($cardFeeRow->fee_amount, 2));
        $this->assertEquals(FeeLedger::STATUS_TRANSFERRED, $cardFeeRow->status);
        $this->assertNotNull($cardFeeRow->transferred_at);

        // 2 total: deposit fee + card_application fee
        $this->assertDatabaseCount('fee_ledger', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 5 — Ledger & margin account integrity
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Full-flow integrity: credits − real debits = current balance.
     * All fee_ledger rows transferred. No pending rows remain.
     *
     * ┌─ CLIENT LEDGER SUMMARY ───────────────────────────────────────────────┐
     * │  deposit               +$480.20  confirmed  (net after 2% deposit fee) │
     * │  platform_fee (dep)    -$  9.80  confirmed  (informational — baked in)  │
     * │  card_create           -$ 50.00  confirmed                              │
     * │  wasabi_card_fee       -$  5.00  confirmed                              │
     * │  wasabi_processing_fee -$  0.50  confirmed                              │
     * │  platform_fee (card)   -$  5.00  confirmed                              │
     * │  ─────────────────────────────────────────────────────────────────────  │
     * │  CURRENT BALANCE       $419.70                                          │
     * └────────────────────────────────────────────────────────────────────────┘
     *
     * ┌─ MARGIN ACCOUNT (fee_ledger) ──────────────────────────────────────────┐
     * │  deposit          $9.80   transferred                                   │
     * │  card_application $5.00   transferred                                   │
     * │  ─────────────────────────────────────────────────────────────────────  │
     * │  TOTAL MARGIN     $14.80                                                 │
     * └────────────────────────────────────────────────────────────────────────┘
     */
    public function test_step5_full_flow_ledger_integrity(): void
    {
        $merchantOrderNo = 'ORDER_CARD_20260422001';
        $wasabiOrderNo   = 'WSB_CARD_ORDER_001';

        $this->runDepositWebhook();
        $this->runCardCreateRequest($merchantOrderNo, $wasabiOrderNo);
        $this->runCardConfirmWebhook($merchantOrderNo, $wasabiOrderNo, 'CARD_ACME_001');

        // ── 6 total ledger rows ───────────────────────────────────────────────
        $this->assertDatabaseCount('client_transactions', 6);

        // ── All confirmed ────────────────────────────────────────────────────
        $this->assertDatabaseMissing('client_transactions', [
            'api_token_id' => $this->client->id,
            'status'       => ClientTransaction::STATUS_PENDING,
        ]);

        // ── Credits ──────────────────────────────────────────────────────────
        $totalCredits = ClientTransaction::where('api_token_id', $this->client->id)
            ->where('type', 'credit')
            ->where('status', ClientTransaction::STATUS_CONFIRMED)
            ->sum('amount');

        $this->assertEquals(480.20, round((float) $totalCredits, 2), 'Total credits should be $480.20');

        // ── Real debits (rows where balance actually changed) ─────────────────
        // Deposit platform_fee is informational (balance_before = balance_after).
        $realDebits = ClientTransaction::where('api_token_id', $this->client->id)
            ->where('type', 'debit')
            ->where('status', ClientTransaction::STATUS_CONFIRMED)
            ->whereRaw('balance_before != balance_after')
            ->sum('amount');

        $this->assertEquals(60.50, round((float) $realDebits, 2), 'Real debits ($50+$5+$0.50+$5) should total $60.50');

        // ── Balance integrity: credits − realDebits = current balance ─────────
        $currentBalance = (float) ClientBalance::where('api_token_id', $this->client->id)
            ->value('balance');

        $expected = round((float) $totalCredits - (float) $realDebits, 2);
        $this->assertEquals(
            $expected,
            round($currentBalance, 2),
            "Ledger reconciliation failed: credits($totalCredits) - debits($realDebits) ≠ balance($currentBalance)"
        );
        $this->assertEquals(419.70, round($currentBalance, 2), 'Final balance should be $419.70');

        // ── Platform margin account ───────────────────────────────────────────
        $this->assertDatabaseCount('fee_ledger', 2);

        $totalMargin = FeeLedger::where('api_token_id', $this->client->id)
            ->where('status', FeeLedger::STATUS_TRANSFERRED)
            ->sum('fee_amount');

        $this->assertEquals(14.80, round((float) $totalMargin, 2), 'Total platform margin should be $14.80 ($9.80 + $5)');

        // No failed fee rows
        $this->assertDatabaseMissing('fee_ledger', [
            'api_token_id' => $this->client->id,
            'status'       => FeeLedger::STATUS_FAILED,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Edge cases
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Duplicate deposit webhook (same X-WSB-REQUEST-ID) must be ignored.
     * Balance and ledger must not change on a replay.
     */
    public function test_duplicate_deposit_webhook_is_idempotent(): void
    {
        $depositOrderNo = 'WSB_WALLET_DEPOSIT_001';

        TenantResource::create([
            'api_token_id'  => $this->client->id,
            'resource_type' => TenantResource::TYPE_ORDER,
            'wasabi_id'     => $depositOrderNo,
        ]);

        $payload = [
            'type'           => 'deposit',
            'status'         => 'success',
            'orderNo'        => $depositOrderNo,
            'receivedAmount' => 490.00,
            'amount'         => 500.00,
        ];

        $headers = [
            'X-WSB-CATEGORY'   => 'wallet_transaction_v2',
            'X-WSB-REQUEST-ID' => 'REQ_DEPOSIT_DUP_001',
        ];

        // First delivery
        $this->postJson('/api/webhook', $payload, $headers)->assertStatus(200);

        $balanceAfterFirst = (float) ClientBalance::where('api_token_id', $this->client->id)->value('balance');
        $countAfterFirst   = ClientTransaction::where('api_token_id', $this->client->id)->count();

        // Second delivery (same request_id — Wasabi retry)
        $this->postJson('/api/webhook', $payload, $headers)->assertStatus(200);

        $balanceAfterSecond = (float) ClientBalance::where('api_token_id', $this->client->id)->value('balance');
        $countAfterSecond   = ClientTransaction::where('api_token_id', $this->client->id)->count();

        $this->assertEquals($balanceAfterFirst, $balanceAfterSecond, 'Balance must not change on duplicate webhook');
        $this->assertEquals($countAfterFirst,   $countAfterSecond,   'Ledger row count must not change on duplicate webhook');
    }

    /**
     * Card creation fails when client has insufficient balance.
     * No ledger rows are written; balance stays unchanged.
     */
    public function test_card_creation_rejected_when_balance_is_zero(): void
    {
        // No deposit — balance remains $0

        $this->mock(CardService::class, function ($mock): void {
            $mock->shouldReceive('getCardTypeById')->once()->andReturn([
                'cardTypeId'                        => 111002,
                'cardPrice'                         => '5',
                'rechargeFeeRate'                   => 1.0,
                'rechargeFixedFee'                  => 0.0,
                'depositAmountMinQuotaForActiveCard' => '20',
            ]);
            $mock->shouldNotReceive('createCardV2');
        });

        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => 'ORDER_CARD_INSUF_001',
            'cardTypeId'      => 111002,
            'amount'          => 50,
        ], ['X-API-KEY' => $this->rawToken]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('client_transactions', 0);

        $balance = (float) ClientBalance::where('api_token_id', $this->client->id)->value('balance');
        $this->assertEquals(0.00, round($balance, 2), 'Balance must remain $0 after rejected card creation');
    }

    /**
     * Card creation webhook failure status reverses the pending reservation.
     * Balance is refunded; all rows become "reversed".
     */
    public function test_card_confirm_webhook_failure_reverses_reservation(): void
    {
        $this->runDepositWebhook();

        $merchantOrderNo = 'ORDER_CARD_FAIL_001';
        $wasabiOrderNo   = 'WSB_CARD_FAIL_001';
        $this->runCardCreateRequest($merchantOrderNo, $wasabiOrderNo);

        $balanceAfterReservation = (float) ClientBalance::where('api_token_id', $this->client->id)->value('balance');
        $this->assertEquals(419.70, round($balanceAfterReservation, 2));

        // Register the Wasabi orderNo so the webhook can resolve api_token_id
        TenantResource::updateOrCreate(
            ['resource_type' => TenantResource::TYPE_ORDER, 'wasabi_id' => $wasabiOrderNo],
            ['api_token_id' => $this->client->id, 'merchant_order_no' => $merchantOrderNo],
        );

        // Wasabi reports card creation failed
        $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'fail',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_CARD_FAIL_001',
        ])->assertStatus(200);

        // Balance fully refunded to $480.20
        $balanceAfterReversal = (float) ClientBalance::where('api_token_id', $this->client->id)->value('balance');
        $this->assertEquals(480.20, round($balanceAfterReversal, 2), 'Balance must be refunded after card failure');

        // All card reservation rows reversed
        $reversedRows = ClientTransaction::where('api_token_id', $this->client->id)
            ->where('reference_id', $merchantOrderNo)
            ->where('status', ClientTransaction::STATUS_REVERSED)
            ->count();

        $this->assertEquals(4, $reversedRows, 'All 4 reservation rows must be reversed on card failure');
    }

    /**
     * Unauthenticated card creation request is rejected with 401.
     */
    public function test_card_creation_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => 'ORDER_CARD_UNAUTH_001',
            'cardTypeId'      => 111002,
            'amount'          => 50,
        ]); // No X-API-KEY header

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers — replay sub-steps for tests that need prior state
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Replay the deposit webhook to get $480.20 into the client balance.
     */
    private function runDepositWebhook(): void
    {
        $depositOrderNo = 'WSB_WALLET_DEPOSIT_001';

        TenantResource::firstOrCreate(
            ['resource_type' => TenantResource::TYPE_ORDER, 'wasabi_id' => $depositOrderNo],
            ['api_token_id' => $this->client->id],
        );

        $this->postJson('/api/webhook', [
            'type'           => 'deposit',
            'status'         => 'success',
            'orderNo'        => $depositOrderNo,
            'receivedAmount' => 490.00,
            'amount'         => 500.00,
            'fee'            => 10.00,
        ], [
            'X-WSB-CATEGORY'   => 'wallet_transaction_v2',
            'X-WSB-REQUEST-ID' => 'REQ_DEPOSIT_HELPER',
        ]);
    }

    /**
     * Replay the card creation request (mocks CardService inline).
     */
    private function runCardCreateRequest(string $merchantOrderNo, string $wasabiOrderNo): void
    {
        $this->mock(CardService::class, function ($mock) use ($merchantOrderNo, $wasabiOrderNo): void {
            $mock->shouldReceive('getCardTypeById')
                ->once()
                ->with(111002)
                ->andReturn([
                    'cardTypeId'                        => 111002,
                    'cardPrice'                         => '5',
                    'rechargeFeeRate'                   => 1.0,
                    'rechargeFixedFee'                  => 0.0,
                    'depositAmountMinQuotaForActiveCard' => '20',
                ]);

            $mock->shouldReceive('createCardV2')
                ->once()
                ->andReturn([
                    'orderNo'         => $wasabiOrderNo,
                    'cardNo'          => null,
                    'status'          => 'processing',
                    'amount'          => '50',
                    'fee'             => '5.5',
                    'receivedAmount'  => '44.5',
                    'merchantOrderNo' => $merchantOrderNo,
                ]);
        });

        $this->postJson('/api/v1/cards/create-v2', [
            'merchantOrderNo' => $merchantOrderNo,
            'cardTypeId'      => 111002,
            'amount'          => 50,
        ], ['X-API-KEY' => $this->rawToken]);
    }

    /**
     * Replay the card confirmation webhook.
     */
    private function runCardConfirmWebhook(string $merchantOrderNo, string $wasabiOrderNo, string $cardNo): void
    {
        TenantResource::updateOrCreate(
            ['resource_type' => TenantResource::TYPE_ORDER, 'wasabi_id' => $wasabiOrderNo],
            ['api_token_id' => $this->client->id, 'merchant_order_no' => $merchantOrderNo],
        );

        $this->postJson('/api/webhook', [
            'type'            => 'create',
            'status'          => 'success',
            'orderNo'         => $wasabiOrderNo,
            'merchantOrderNo' => $merchantOrderNo,
            'cardNo'          => $cardNo,
            'amount'          => '50',
            'fee'             => '5.5',
            'receivedAmount'  => '44.5',
        ], [
            'X-WSB-CATEGORY'   => 'card_transaction',
            'X-WSB-REQUEST-ID' => 'REQ_CONFIRM_HELPER',
        ]);
    }
}
