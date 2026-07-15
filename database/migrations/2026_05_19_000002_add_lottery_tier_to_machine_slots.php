<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_slots', function (Blueprint $table): void {
            // Null = slot not part of any lottery tier.
            // 'A'  = Tier A (rare prize bucket).
            // 'B'  = Tier B (common prize bucket).
            $table->string('lottery_tier', 1)
                ->nullable()
                ->after('is_fault')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $table): void {
            $table->dropIndex(['lottery_tier']);
            $table->dropColumn('lottery_tier');
        });
    }
};
