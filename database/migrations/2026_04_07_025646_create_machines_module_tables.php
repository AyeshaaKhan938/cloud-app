<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('operation_and_maintenance_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('finance_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('machine_number')->unique();
            $table->string('machine_name');
            $table->foreignId('machine_group_id')
                ->nullable()
                ->constrained('machine_groups')
                ->nullOnDelete();
            $table->foreignId('finance_group_id')
                ->nullable()
                ->constrained('finance_groups')
                ->nullOnDelete();
            $table->string('machine_scenario')->nullable();
            $table->string('service_hot_line')->nullable();
            $table->text('detailed_address')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->text('remarks')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('machine_label_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('machine_label_group_machine', function (Blueprint $table) {
            $table->foreignId('machine_label_group_id')
                ->constrained('machine_label_groups')
                ->cascadeOnDelete();
            $table->foreignId('machine_id')
                ->constrained('machines')
                ->cascadeOnDelete();
            $table->primary(['machine_label_group_id', 'machine_id']);
        });

        Schema::create('machine_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')
                ->constrained('machines')
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('severity')->default('warning');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_alarms');
        Schema::dropIfExists('machine_label_group_machine');
        Schema::dropIfExists('machine_label_groups');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('finance_groups');
        Schema::dropIfExists('machine_groups');
    }
};
