<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_age_verification')->default(false)->after('is_active');
            $table->unsignedTinyInteger('minimum_age')->nullable()->after('requires_age_verification');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['requires_age_verification', 'minimum_age']);
        });
    }
};
