<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('account_email_notification')->default(false);
            $table->boolean('inventory_shortage_notification')->default(false);
            $table->boolean('equipment_offline_notification')->default(false);
            $table->boolean('slot_failure_notification')->default(false);
            $table->boolean('network_anomaly_notification')->default(false);
            $table->string('notification_email', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
