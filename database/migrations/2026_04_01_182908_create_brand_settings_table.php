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
        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->string('homepage_logo_path')->nullable();
            $table->string('homepage_icon_path')->nullable();
            $table->string('homepage_promotion_image_path')->nullable();
            $table->string('homepage_background_image_path')->nullable();
            $table->string('device_startup_animation_path')->nullable();
            $table->string('homepage_bottom_logo_path')->nullable();
            $table->string('device_bottom_logo_path')->nullable();
            $table->string('default_webpage_title')->default('VMFS USA Cloud');
            $table->string('homepage_logo_jump_link')->nullable();
            $table->boolean('device_default_ad_eliminates_logo')->default(false);
            $table->longText('homepage_footer_html')->nullable();
            $table->timestamps();
        });

        DB::table('brand_settings')->insert([
            'default_webpage_title' => 'VMFS USA Cloud',
            'homepage_logo_jump_link' => 'vmfsusa.com',
            'device_default_ad_eliminates_logo' => false,
            'homepage_footer_html' => '<p>2020-2026 VMFS USA™ All Rights Reserved</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_settings');
    }
};
