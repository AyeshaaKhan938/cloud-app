<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('machine_id')
                ->nullable()
                ->after('user_id')
                ->constrained('machines')
                ->nullOnDelete();
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->after('machine_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('submitted_at');
            $table->timestamp('last_message_at')->nullable()->after('resolved_at');
            $table->timestamp('live_chat_requested_at')->nullable()->after('last_message_at');
            $table->boolean('live_chat_active')->default(false)->after('live_chat_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropConstrainedForeignId('machine_id');
            $table->dropColumn([
                'resolved_at',
                'last_message_at',
                'live_chat_requested_at',
                'live_chat_active',
            ]);
        });
    }
};
