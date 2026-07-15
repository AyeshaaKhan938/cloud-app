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
        Schema::create('lottery_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tier_a_name')->default('Grand Prize');
            $table->unsignedInteger('tier_a_weight')->default(1);
            $table->string('tier_b_name')->default('Consolation');
            $table->unsignedInteger('tier_b_weight')->default(49);
            $table->timestamps();
        });

        // Seed the single row this table will ever hold.
        DB::table('lottery_settings')->insert([
            'tier_a_name' => 'Grand Prize',
            'tier_a_weight' => 1,
            'tier_b_name' => 'Consolation',
            'tier_b_weight' => 49,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_settings');
    }
};
