<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add api_token_id to webhook_events so each event is scoped to the tenant
     * whose resource triggered the webhook. Nullable because events that arrive
     * for unregistered resources (e.g. direct Wasabi dashboard actions) cannot
     * be linked to a tenant.
     */
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->foreignId('api_token_id')
                ->nullable()
                ->after('id')
                ->constrained('api_tokens')
                ->nullOnDelete()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('api_token_id');
        });
    }
};
