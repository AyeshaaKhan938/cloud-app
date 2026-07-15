<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra machine_line_product_id → line_number.
 *
 * El número de slot/línea físico de la máquina (ej. 1, 2, 3…) es suficiente
 * para enviar el comando de despacho al Control Board vía UART.
 * No necesitamos un ID externo de un sistema de terceros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lottery_prizes', function (Blueprint $table): void {
            $table->renameColumn('machine_line_product_id', 'line_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_lottery_prizes', function (Blueprint $table): void {
            $table->renameColumn('line_number', 'machine_line_product_id');
        });
    }
};
