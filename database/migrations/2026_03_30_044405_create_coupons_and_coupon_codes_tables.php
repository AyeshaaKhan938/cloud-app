<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('purchase_amount', 12, 2)->default(0);
            $table->string('coupon_type');
            $table->decimal('discount_value', 12, 2);
            $table->unsignedInteger('usage_frequency');
            $table->string('generation_rule');
            $table->string('distribution_rule');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->unsignedInteger('times_used')->default(0);
            $table->unsignedInteger('max_uses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_codes');
        Schema::dropIfExists('coupons');
    }
};
