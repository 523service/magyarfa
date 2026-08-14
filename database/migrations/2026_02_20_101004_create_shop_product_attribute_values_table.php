<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('shop_attribute_id')->constrained('shop_attributes')->cascadeOnDelete();
            $table->text('text_value')->nullable();
            $table->decimal('number_value', 10, 2)->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();

            // Ensure unique combination of product and attribute
            $table->unique(['shop_product_id', 'shop_attribute_id'], 'product_attribute_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_attribute_values');
    }
};
