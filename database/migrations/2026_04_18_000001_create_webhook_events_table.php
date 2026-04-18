<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();

            // X-WSB-REQUEST-ID: Wasabi unique request ID — used for idempotency
            $table->string('request_id', 100)->nullable()->unique();

            // X-WSB-CATEGORY: e.g. card_holder, card_transaction, wallet_transaction
            $table->string('category', 60)->index();

            // Primary entity ID extracted from payload:
            //   card_holder / card_holder_change_email → holderId
            //   card_auth_transaction / card_fee_patch / card_3ds → tradeNo
            //   card_transaction / work / wallet_transaction / wallet_transaction_v2 → orderNo
            //   physical_card → cardNo
            $table->string('reference_id', 100)->nullable()->index();

            // merchantOrderNo extracted from payload if present
            $table->string('merchant_order_no', 60)->nullable()->index();

            // status or tradeStatus extracted from payload
            $table->string('status', 40)->nullable()->index();

            // Full raw payload from Wasabi
            $table->json('payload');

            // Whether the X-WSB-SIGNATURE RSA verification passed
            $table->boolean('signature_verified')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
