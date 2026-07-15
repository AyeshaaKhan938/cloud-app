<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lotteries', function (Blueprint $table) {
            $table->string('public_draw_token', 64)->nullable()->unique();
        });

        foreach (DB::table('product_lotteries')->orderBy('id')->cursor() as $row) {
            DB::table('product_lotteries')->where('id', $row->id)->update([
                'public_draw_token' => Str::lower((string) Str::ulid()),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('product_lotteries', function (Blueprint $table) {
            $table->dropUnique(['public_draw_token']);
            $table->dropColumn('public_draw_token');
        });
    }
};
