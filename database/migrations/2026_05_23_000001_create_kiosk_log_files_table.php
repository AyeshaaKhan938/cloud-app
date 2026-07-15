<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for log files uploaded from kiosks via the admin "Send to
 * vms-cloud" button. Each row is one upload event: the file itself
 * lives on disk under storage/app/kiosk-logs/{machine_no}/.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_log_files', function (Blueprint $table): void {
            $table->id();

            // Which kiosk sent it. We don't foreign-key into machines.id
            // because kiosks can upload before they've been provisioned
            // server-side — store the raw machine_number string instead.
            $table->string('machine_number', 64)->index();

            // File metadata.
            $table->string('original_filename', 255);  // e.g. "2026-05-23.log"
            $table->string('stored_path', 512);        // relative path on disk
            $table->unsignedBigInteger('size_bytes');

            // Optional: short version of the kiosk app the file came from.
            $table->string('app_version', 32)->nullable();

            // Optional: 64-hex sha256 of the file for de-dup detection.
            $table->string('sha256', 64)->nullable()->index();

            // Optional notes the operator can add via Filament admin.
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['machine_number', 'created_at'], 'kiosk_log_files_machine_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_log_files');
    }
};
