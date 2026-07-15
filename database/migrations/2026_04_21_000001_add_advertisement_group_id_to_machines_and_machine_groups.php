<?php

declare(strict_types=1);

use App\Models\AdvertisementGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table): void {
            $table->foreignId('advertisement_group_id')
                ->nullable()
                ->after('finance_group_id')
                ->constrained('advertisement_groups')
                ->nullOnDelete();
        });

        Schema::table('machine_groups', function (Blueprint $table): void {
            $table->foreignId('advertisement_group_id')
                ->nullable()
                ->after('operation_and_maintenance_user_id')
                ->constrained('advertisement_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table): void {
            $table->dropForeignIdFor(AdvertisementGroup::class);
            $table->dropColumn('advertisement_group_id');
        });

        Schema::table('machine_groups', function (Blueprint $table): void {
            $table->dropForeignIdFor(AdvertisementGroup::class);
            $table->dropColumn('advertisement_group_id');
        });
    }
};
