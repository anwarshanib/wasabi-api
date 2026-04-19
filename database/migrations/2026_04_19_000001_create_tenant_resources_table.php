<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which Wasabi resources (cardholders, cards, wallet addresses, orders)
     * belong to which third-party API token.
     *
     * Because your infrastructure uses one Wasabi merchant account shared across
     * multiple third parties, this table is the source of truth for tenant
     * isolation. Every resource created via the API is registered here, and every
     * read/modify request checks ownership before calling Wasabi.
     */
    public function up(): void
    {
        Schema::create('tenant_resources', function (Blueprint $table): void {
            $table->id();

            // The third-party API token that owns this resource
            $table->foreignId('api_token_id')
                ->constrained('api_tokens')
                ->cascadeOnDelete();

            // Resource type — cardholder | card | wallet_address | order
            $table->string('resource_type', 30)->index();

            // Wasabi-side identifier:
            //   cardholder    → holderId (integer stored as string)
            //   card          → cardNo   (e.g. "WB202602102021097685055463424")
            //   wallet_address → address (e.g. "TF9fZHD27TmEznSRHcirWkXj2asg24kl3jg")
            //   order         → orderNo  (e.g. "1852379830190366720")
            $table->string('wasabi_id', 120);

            // merchantOrderNo used at creation time — kept for audit / debugging
            $table->string('merchant_order_no', 100)->nullable();

            // A (resource_type, wasabi_id) pair is globally unique — one Wasabi
            // resource can only belong to one tenant.
            $table->unique(['resource_type', 'wasabi_id']);

            // Composite index for fast ownership lookups
            $table->index(['api_token_id', 'resource_type']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_resources');
    }
};
