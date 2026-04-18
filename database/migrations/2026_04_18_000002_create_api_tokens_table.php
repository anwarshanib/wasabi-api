<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();

            // Human-readable label for the token (e.g. "Acme Corp - Production")
            $table->string('name', 120);

            // Developer / company contact email
            $table->string('email', 120)->nullable();

            // Optional notes (e.g. "Sandbox access only")
            $table->string('description', 255)->nullable();

            // SHA-256 hash of the token — used by middleware for fast, safe lookup
            $table->string('token_hash', 64)->unique();

            // Encrypted copy of the raw token — used by admin UI to display it once
            $table->text('token_encrypted');

            // Active flag — admin can disable without deleting
            $table->boolean('is_active')->default(true)->index();

            // Last time this key was used for an API request
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
