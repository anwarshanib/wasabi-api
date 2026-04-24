<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('api_token_id')->nullable()->constrained('api_tokens')->nullOnDelete();
            $table->string('fee_type', 30);                         // deposit | card_application | fx
            $table->decimal('base_amount', 16, 4);                  // original transaction amount
            $table->decimal('fee_amount', 16, 4);                   // calculated fee
            $table->char('currency', 3)->default('USD');
            $table->string('reference_id', 120)->nullable();        // orderNo / tradeNo / merchantOrderNo
            $table->enum('status', ['pending', 'transferred', 'failed'])->default('pending');
            $table->string('wasabi_order_no', 120)->nullable();      // our FEE_* merchantOrderNo sent to Wasabi
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->index(['fee_type', 'reference_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_ledger');
    }
};
