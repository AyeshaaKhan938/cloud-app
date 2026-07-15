<?php

declare(strict_types=1);

use App\Enums\AdvertisementGroupSlot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisement_group_advertisement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_group_id')->constrained('advertisement_groups')->cascadeOnDelete();
            $table->foreignId('advertisement_id')->constrained('advertisements')->cascadeOnDelete();
            $table->string('slot', 32);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['advertisement_group_id', 'advertisement_id', 'slot'],
                'ad_group_advertisement_slot_unique'
            );
        });

        if (Schema::hasColumn('advertisements', 'advertisement_group_id')) {
            $rows = DB::table('advertisements')
                ->whereNotNull('advertisement_group_id')
                ->orderBy('id')
                ->get(['id', 'advertisement_group_id']);

            $now = now();
            foreach ($rows as $row) {
                DB::table('advertisement_group_advertisement')->insert([
                    'advertisement_group_id' => $row->advertisement_group_id,
                    'advertisement_id' => $row->id,
                    'slot' => AdvertisementGroupSlot::Screensaver->value,
                    'sort_order' => (int) $row->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('advertisements', function (Blueprint $table) {
                $table->dropForeign(['advertisement_group_id']);
                $table->dropColumn('advertisement_group_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->foreignId('advertisement_group_id')
                ->nullable()
                ->after('id')
                ->constrained('advertisement_groups')
                ->nullOnDelete();
        });

        $pivotRows = DB::table('advertisement_group_advertisement')
            ->where('slot', AdvertisementGroupSlot::Screensaver->value)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['advertisement_id', 'advertisement_group_id']);

        foreach ($pivotRows as $row) {
            DB::table('advertisements')
                ->where('id', $row->advertisement_id)
                ->whereNull('advertisement_group_id')
                ->update(['advertisement_group_id' => $row->advertisement_group_id]);
        }

        Schema::dropIfExists('advertisement_group_advertisement');
    }
};
