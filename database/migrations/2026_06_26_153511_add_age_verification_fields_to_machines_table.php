<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->boolean('age_verification_enabled')->default(false)->after('is_enabled');
            $table->unsignedTinyInteger('minimum_age')->default(18)->after('age_verification_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['age_verification_enabled', 'minimum_age']);
        });
    }
};
