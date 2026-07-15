<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number', 100)->unique();
            $table->string('device_number', 100)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->string('associated_account', 255)->nullable();
            $table->string('device_type', 100)->nullable();
            $table->string('submitted_by', 255)->nullable();
            $table->text('issue_description')->nullable();
            $table->timestamp('submitted_at');
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->string('priority', 32);
            $table->string('reporting_status', 32);
            $table->string('status', 32);
            $table->timestamps();

            $table->index(['status', 'reporting_status']);
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
