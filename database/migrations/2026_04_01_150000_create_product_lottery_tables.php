<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lotteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('generation_rule');
            $table->timestamps();
        });

        Schema::create('product_lottery_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_lottery_id')->constrained()->cascadeOnDelete();
            $table->string('tier_code', 32);
            $table->string('name')->nullable();
            $table->decimal('prize_amount', 12, 2)->default(0);
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_lottery_id');
        });

        Schema::create('product_lottery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_lottery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_lottery_prize_id')->constrained()->restrictOnDelete();
            $table->string('code', 32)->unique();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index('product_lottery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lottery_codes');
        Schema::dropIfExists('product_lottery_prizes');
        Schema::dropIfExists('product_lotteries');
    }
};
