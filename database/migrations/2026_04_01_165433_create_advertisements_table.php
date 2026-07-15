<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_group_id')
                ->nullable()
                ->constrained('advertisement_groups')
                ->nullOnDelete();
            $table->string('title');
            $table->string('type', 32);
            $table->string('media_path');
            $table->string('link_url', 2048)->nullable();
            $table->string('advertiser_name')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('remarks', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
