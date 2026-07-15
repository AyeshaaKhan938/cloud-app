<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_verification_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->string('machine_no', 64)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('age_verified')->default(false);
            $table->string('document_type', 32)->nullable();
            $table->string('provider_ref')->nullable()->index();
            $table->string('document_path')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_verification_sessions');
    }
};
