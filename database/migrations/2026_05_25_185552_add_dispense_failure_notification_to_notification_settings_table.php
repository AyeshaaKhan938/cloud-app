<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->boolean('dispense_failure_notification')
                ->default(true)
                ->after('slot_failure_notification');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->dropColumn('dispense_failure_notification');
        });
    }
};
