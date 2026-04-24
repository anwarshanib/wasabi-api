<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('api_token_id')->unique()->constrained('api_tokens')->cascadeOnDelete();
            $table->decimal('balance', 16, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_balances');
    }
};
