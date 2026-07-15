<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('specifications')) {
            return;
        }

        DB::table('specifications')
            ->where('specification_type', 'by_weight')
            ->update(['specification_type' => 'weight_ambp_500']);

        DB::table('specifications')
            ->where('specification_type', 'by_volume')
            ->update(['specification_type' => 'capacity']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('specifications')) {
            return;
        }

        DB::table('specifications')
            ->where('specification_type', 'weight_ambp_500')
            ->update(['specification_type' => 'by_weight']);

        DB::table('specifications')
            ->where('specification_type', 'capacity')
            ->update(['specification_type' => 'by_volume']);
    }
};
