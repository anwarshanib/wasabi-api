<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('fee_type', 30)->unique();   // deposit | card_application | fx
            $table->decimal('rate', 10, 4)->default(0); // percentage (e.g. 1.0000 = 1%)
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Seed three inactive rows at rate=0
        DB::table('fee_settings')->insert([
            ['fee_type' => 'deposit',          'rate' => 0, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['fee_type' => 'card_application', 'rate' => 0, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['fee_type' => 'fx',               'rate' => 0, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_settings');
    }
};
