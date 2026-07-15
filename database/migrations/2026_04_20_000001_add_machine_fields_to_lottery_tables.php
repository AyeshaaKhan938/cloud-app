<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula el sistema de lotería con la vending machine física (Reyeah Cloud API).
 *
 * product_lotteries:
 *   - machine_no         → número de serie de la máquina (machineNo en Reyeah)
 *
 * product_lottery_prizes:
 *   - machine_line_product_id → ID del slot en Reyeah (machineLineProductId),
 *                               necesario para crear una orden y despachar el producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lotteries', function (Blueprint $table): void {
            $table->string('machine_no', 64)
                ->nullable()
                ->after('public_draw_token')
                ->comment('Reyeah machineNo — número de serie de la vending machine');
        });

        Schema::table('product_lottery_prizes', function (Blueprint $table): void {
            $table->string('machine_line_product_id', 64)
                ->nullable()
                ->after('sort_order')
                ->comment('Reyeah machineLineProductId — ID del slot a despachar al ganar este prize');
        });
    }

    public function down(): void
    {
        Schema::table('product_lottery_prizes', function (Blueprint $table): void {
            $table->dropColumn('machine_line_product_id');
        });

        Schema::table('product_lotteries', function (Blueprint $table): void {
            $table->dropColumn('machine_no');
        });
    }
};
