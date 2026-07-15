<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * machine_slots — inventario de slots físicos de cada vending machine.
 *
 * Cada fila = un slot/línea de la máquina con su producto asignado y stock actual.
 * Es el corazón del sistema: sin esta tabla no hay Product Browser ni despacho.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_slots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('machine_id')
                ->constrained()
                ->cascadeOnDelete();

            // Número de línea/slot físico dentro de la máquina (1, 2, 3…)
            $table->unsignedSmallInteger('line_number');

            // Producto asignado a este slot (nullable = slot vacío / sin configurar)
            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Precio de venta en este slot (puede diferir del precio base del producto)
            $table->decimal('price', 10, 2)->default(0);

            // Inventario
            $table->unsignedSmallInteger('max_stock')->default(10);
            $table->unsignedSmallInteger('current_stock')->default(0);
            $table->unsignedTinyInteger('stock_alarm_threshold')->default(3)
                ->comment('Alerta cuando el stock baja de este umbral');

            // Estado
            $table->boolean('is_active')->default(true);
            $table->boolean('is_fault')->default(false)
                ->comment('true si el slot tiene una falla física reportada');

            $table->timestamps();

            // Un slot (line_number) es único dentro de cada máquina
            $table->unique(['machine_id', 'line_number']);
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_slots');
    }
};
