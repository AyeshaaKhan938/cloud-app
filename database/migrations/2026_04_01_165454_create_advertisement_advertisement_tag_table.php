<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisement_advertisement_tag', function (Blueprint $table) {
            $table->foreignId('advertisement_id')
                ->constrained('advertisements')
                ->cascadeOnDelete();
            $table->foreignId('advertisement_tag_id')
                ->constrained('advertisement_tags')
                ->cascadeOnDelete();
            $table->primary(['advertisement_id', 'advertisement_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisement_advertisement_tag');
    }
};
