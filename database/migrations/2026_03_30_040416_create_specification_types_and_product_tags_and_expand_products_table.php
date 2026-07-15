<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('specification_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('product_tag_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('main_image')->nullable();
            $table->string('paypal_currency', 8)->nullable();
            $table->string('brand')->nullable();
            $table->string('product_number')->nullable();
            $table->json('media_expansions')->nullable();
            $table->json('product_tones')->nullable();
            $table->string('model_3d_path')->nullable();
            $table->text('product_remarks')->nullable();
            $table->longText('product_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['specification_type_id']);
            $table->dropForeign(['product_tag_id']);
            $table->dropColumn([
                'specification_type_id',
                'product_tag_id',
                'main_image',
                'paypal_currency',
                'brand',
                'product_number',
                'media_expansions',
                'product_tones',
                'model_3d_path',
                'product_remarks',
                'product_details',
            ]);
        });

        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('specification_types');
    }
};
