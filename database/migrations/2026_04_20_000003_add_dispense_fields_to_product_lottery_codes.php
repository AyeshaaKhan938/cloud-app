<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos de trazabilidad de despacho físico a product_lottery_codes.
 *
 * La Flutter app reporta el resultado del despacho al Control Board
 * y estos campos quedan registrados para auditoría en el cPanel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lottery_codes', function (Blueprint $table): void {
            $table->string('dispense_status', 16)->nullable()->after('redeemed_at')
                ->comment('success | failed — resultado del despacho físico');
            $table->string('dispense_machine_no', 64)->nullable()->after('dispense_status')
                ->comment('Número de serie de la máquina que despachó');
            $table->unsignedSmallInteger('dispense_line')->nullable()->after('dispense_machine_no')
                ->comment('Número de slot/línea que se despachó');
            $table->string('dispense_error', 255)->nullable()->after('dispense_line')
                ->comment('Mensaje de error si dispense_status=failed');
            $table->timestamp('dispensed_at')->nullable()->after('dispense_error')
                ->comment('Timestamp del despacho exitoso');
        });
    }

    public function down(): void
    {
        Schema::table('product_lottery_codes', function (Blueprint $table): void {
            $table->dropColumn([
                'dispense_status',
                'dispense_machine_no',
                'dispense_line',
                'dispense_error',
                'dispensed_at',
            ]);
        });
    }
};
