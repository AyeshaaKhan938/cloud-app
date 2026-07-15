<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account', 100)->nullable()->unique()->after('id');
            $table->string('phone', 50)->nullable()->after('password');
            $table->string('timezone', 64)->default('UTC')->after('phone');
            $table->string('role', 32)->nullable()->after('timezone');
            $table->boolean('is_enabled')->default(true)->after('role');
            $table->string('country', 2)->nullable()->after('is_enabled');
            $table->string('region', 100)->nullable()->after('country');
            $table->text('contact_emails')->nullable()->after('email');
            $table->string('registration_method', 32)->nullable()->after('contact_emails');
            $table->string('client_version', 32)->nullable()->after('registration_method');
            $table->string('login_address', 255)->nullable()->after('client_version');
            $table->foreignId('created_by')->nullable()->after('login_address')->constrained('users')->nullOnDelete();
        });

        foreach (DB::table('users')->orderBy('id')->cursor() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'account' => 'user_'.$row->id,
                'timezone' => 'UTC',
                'role' => 'operator',
                'is_enabled' => true,
                'registration_method' => 'email',
                'contact_emails' => $row->email,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'account',
                'phone',
                'timezone',
                'role',
                'is_enabled',
                'country',
                'region',
                'contact_emails',
                'registration_method',
                'client_version',
                'login_address',
                'created_by',
            ]);
        });
    }
};
