<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('api_token_id')->constrained('api_tokens')->cascadeOnDelete();

            // credit = money in, debit = money out
            $table->enum('type', ['credit', 'debit']);

            /*
             * Event types (what caused this balance movement):
             *   deposit            – crypto on-chain deposit confirmed
             *   card_create        – card creation (initial deposit + platform fee)
             *   card_deposit       – top-up to existing card
             *   card_withdraw      – withdraw from card back to wallet
             *   card_cancel_refund – remaining balance returned when card cancelled
             *   platform_fee       – platform card application / deposit fee
             *   auth_fee_patch     – Wasabi debited merchant reserve for card auth fee
             *   cross_border_fee   – cross-border transaction fee
             *   overdraft          – card overdraft bill charged to merchant wallet
             *   adjustment         – manual/admin correction
             */
            $table->string('event', 40);

            $table->decimal('amount', 16, 4);        // absolute value, always positive
            $table->decimal('balance_before', 16, 4);
            $table->decimal('balance_after', 16, 4);

            $table->string('reference_id', 100)->nullable(); // Wasabi orderNo / tradeNo / cardNo
            $table->string('currency', 10)->default('USD');

            /*
             * pending   – debit reserved before Wasabi API call (pre-auth)
             * confirmed – Wasabi webhook confirmed success (or credit received)
             * reversed  – Wasabi reported failure; pending debit reversed
             */
            $table->enum('status', ['pending', 'confirmed', 'reversed'])->default('confirmed');

            $table->json('meta')->nullable(); // raw payload amounts, fee breakdown, etc.
            $table->timestamps();

            $table->index(['api_token_id', 'event']);
            $table->index(['api_token_id', 'created_at']);
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_transactions');
    }
};
