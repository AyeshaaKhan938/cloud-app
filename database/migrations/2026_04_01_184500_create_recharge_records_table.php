<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_account', 255);
            $table->string('machine_number', 100);
            $table->decimal('amount', 14, 2);
            $table->text('detail')->nullable();
            $table->string('service_type', 100);
            $table->timestamp('ordered_at');
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['ordered_at', 'paid_at']);
            $table->index('machine_number');
            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_records');
    }
};
