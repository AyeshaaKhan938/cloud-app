<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 14, 2)->default(0)->after('remember_token');
            $table->decimal('wallet_excess_amount', 14, 2)->default(0)->after('wallet_balance');
            $table->decimal('wallet_recharge_pending', 14, 2)->default(0)->after('wallet_excess_amount');
            $table->decimal('wallet_accumulated_recharge', 14, 2)->default(0)->after('wallet_recharge_pending');
            $table->decimal('wallet_withdrawal_pending', 14, 2)->default(0)->after('wallet_accumulated_recharge');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'wallet_balance',
                'wallet_excess_amount',
                'wallet_recharge_pending',
                'wallet_accumulated_recharge',
                'wallet_withdrawal_pending',
            ]);
        });
    }
};
