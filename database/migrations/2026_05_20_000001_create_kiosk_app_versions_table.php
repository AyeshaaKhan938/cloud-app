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
        Schema::create('kiosk_app_versions', function (Blueprint $table): void {
            $table->id();
            // versionCode from build.gradle (monotonically increasing integer).
            $table->unsignedInteger('version_code');
            // versionName e.g. "1.2.0" — shown to operators, not used for comparison.
            $table->string('version_name', 32);
            // Public URL where the APK can be downloaded.
            $table->string('apk_url', 500);
            // SHA-256 of the APK (optional, lets the kiosk verify integrity).
            $table->string('apk_sha256', 64)->nullable();
            $table->unsignedBigInteger('apk_size_bytes')->nullable();
            // Release notes shown in admin "what's new".
            $table->text('release_notes')->nullable();
            // Active = will be served to clients on update-check.
            $table->boolean('is_active')->default(false);
            // Mandatory = kiosk should auto-install without admin confirmation.
            $table->boolean('mandatory')->default(false);
            $table->timestamps();

            $table->index('version_code');
            $table->index('is_active');
        });

        // Seed a row matching the version currently shipped in the APK so we
        // never have an empty table (the API returns "no update available"
        // when no rows are active, which is fine, but tests are easier with
        // a known baseline).
        DB::table('kiosk_app_versions')->insert([
            'version_code' => 1,
            'version_name' => '1.0.0',
            'apk_url' => '',
            'release_notes' => 'Initial baseline (no APK uploaded yet).',
            'is_active' => false,
            'mandatory' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_app_versions');
    }
};
