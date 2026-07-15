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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('specification_id')
                ->nullable()
                ->after('is_active')
                ->constrained('specifications')
                ->nullOnDelete();
        });

        $this->backfillSpecificationIdsFromLegacyTypes();

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['specification_type_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specification_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('specification_type_id')
                ->nullable()
                ->after('is_active')
                ->constrained('specification_types')
                ->nullOnDelete();
        });

        $this->backfillSpecificationTypeIdsFromSpecifications();

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['specification_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specification_id');
        });
    }

    private function backfillSpecificationIdsFromLegacyTypes(): void
    {
        $rows = DB::table('products')
            ->whereNotNull('specification_type_id')
            ->get(['id', 'specification_type_id']);

        foreach ($rows as $row) {
            $typeName = DB::table('specification_types')
                ->where('id', $row->specification_type_id)
                ->value('name');

            if (! is_string($typeName) || $typeName === '') {
                continue;
            }

            $specId = DB::table('specifications')
                ->where('name', $typeName)
                ->value('id');

            if ($specId !== null) {
                DB::table('products')
                    ->where('id', $row->id)
                    ->update(['specification_id' => $specId]);
            }
        }
    }

    private function backfillSpecificationTypeIdsFromSpecifications(): void
    {
        $rows = DB::table('products')
            ->whereNotNull('specification_id')
            ->get(['id', 'specification_id']);

        foreach ($rows as $row) {
            $specName = DB::table('specifications')
                ->where('id', $row->specification_id)
                ->value('name');

            if (! is_string($specName) || $specName === '') {
                continue;
            }

            $typeId = DB::table('specification_types')
                ->where('name', $specName)
                ->value('id');

            if ($typeId !== null) {
                DB::table('products')
                    ->where('id', $row->id)
                    ->update(['specification_type_id' => $typeId]);
            }
        }
    }
};
