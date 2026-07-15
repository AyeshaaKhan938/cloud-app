<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')
                ->constrained('work_orders')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('author_name', 255);
            $table->text('body');
            $table->boolean('is_staff_reply')->default(false);
            $table->timestamps();

            $table->index(['work_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_messages');
    }
};
