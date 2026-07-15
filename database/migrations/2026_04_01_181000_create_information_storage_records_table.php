<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information_storage_records', function (Blueprint $table) {
            $table->id();
            $table->string('collection_method', 32);
            $table->string('ic_card_number', 100);
            $table->string('user_name', 255);
            $table->string('account', 255)->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('promotion_plan', 255)->nullable();
            $table->string('rule_type', 16);
            $table->decimal('points', 12, 2)->nullable();
            $table->unsignedInteger('available_times_in_cycle')->nullable();
            $table->unsignedInteger('used_times_in_cycle')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique('ic_card_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_storage_records');
    }
};
