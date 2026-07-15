<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_records', function (Blueprint $table) {
            $table->id();
            $table->string('message_title', 255);
            $table->string('push_method', 32);
            $table->string('publisher_account', 255);
            $table->timestamp('pushed_at');
            $table->timestamps();

            $table->index(['message_title', 'push_method']);
            $table->index('pushed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_records');
    }
};
