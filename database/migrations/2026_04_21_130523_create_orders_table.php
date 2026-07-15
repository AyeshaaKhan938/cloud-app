<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla orders — registra cada transacción completada en la vending machine.
 *
 * Relaciones:
 *   orders.product_lottery_code_id → product_lottery_codes.id   (trazabilidad del sorteo)
 *   orders.machine_slot_id         → machine_slots.id            (slot físico que se despachó)
 *
 * Una orden se crea cuando la Flutter app reporta el resultado del despacho.
 * Si el pago falla o el usuario cancela, NO se crea orden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            // ── Máquina ──────────────────────────────────────────────────────
            $table->string('machine_no', 64)->index()
                ->comment('Número de serie de la máquina (desnormalizado para filtros)');

            // ── Trazabilidad ─────────────────────────────────────────────────
            $table->foreignId('product_lottery_code_id')
                ->nullable()
                ->constrained('product_lottery_codes')
                ->nullOnDelete()
                ->comment('Código de lotería usado en esta transacción');

            $table->foreignId('machine_slot_id')
                ->nullable()
                ->constrained('machine_slots')
                ->nullOnDelete()
                ->comment('Slot físico que dispensó el producto');

            // ── Producto (desnormalizado para historial) ──────────────────────
            $table->string('product_name', 120)->nullable()
                ->comment('Nombre del producto al momento de la venta');

            $table->unsignedSmallInteger('line_number')->nullable()
                ->comment('Número de línea/slot de la máquina');

            // ── Premio ────────────────────────────────────────────────────────
            $table->string('prize_name', 80)->nullable()
                ->comment('Nombre del premio ganado (tier: Gold, Silver…)');

            $table->decimal('prize_amount', 10, 2)->default(0)
                ->comment('Precio ganado — lo que pagó el cliente');

            // ── Pago ──────────────────────────────────────────────────────────
            $table->string('payment_method', 32)->default('cash')
                ->comment('cash | card | other');

            $table->string('payment_reference', 128)->nullable()
                ->comment('Referencia del terminal de pago (auth code, etc.)');

            // ── Estado ────────────────────────────────────────────────────────
            $table->string('status', 16)->default('completed')
                ->comment('completed | failed | refunded');

            $table->text('notes')->nullable()
                ->comment('Notas libres: error de despacho, motivo de refund, etc.');

            $table->timestamp('completed_at')->nullable()
                ->comment('Cuándo se confirmó el despacho exitoso');

            $table->timestamps();

            // ── Índices para reportes ─────────────────────────────────────────
            $table->index(['machine_no', 'status']);
            $table->index(['completed_at']);
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
