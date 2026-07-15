<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name', 255);
            $table->string('equipment_number', 100);
            $table->timestamp('expires_at');
            $table->decimal('yearly_renewal_amount', 12, 2);
            $table->timestamps();

            $table->index(['user_id', 'equipment_number']);
        });

        Schema::create('renewal_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_account', 255);
            $table->string('user_name', 255);
            $table->string('renewal_account', 255)->nullable();
            $table->string('renewal_number', 100)->unique();
            $table->string('order_number', 100)->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('renew_equipment');
            $table->string('renewal_schedule', 64);
            $table->string('renewal_progress', 32);
            $table->string('pay_type', 32);
            $table->timestamp('application_time');
            $table->timestamps();

            $table->index('application_time');
            $table->index('order_number');
            $table->index('renewal_schedule');
            $table->index('pay_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_histories');
        Schema::dropIfExists('renewal_equipment');
    }
};
