<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenpoint_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('machine_no')->index();

            // Filled at validation time
            $table->timestamp('validated_at');

            // Filled by the kiosk after the client-side 1:49 dice roll + dispense
            $table->string('tier', 8)->nullable();
            $table->unsignedInteger('line_number')->nullable();
            $table->decimal('prize_amount', 8, 2)->nullable();
            $table->boolean('dispense_success')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('dispense_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenpoint_redemptions');
    }
};
