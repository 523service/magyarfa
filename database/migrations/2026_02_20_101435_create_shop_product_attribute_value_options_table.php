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
        Schema::create('shop_product_attribute_value_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_attribute_value_id')
                ->constrained('shop_product_attribute_values')
                ->cascadeOnDelete()
                ->name('product_attr_value_fk');
            $table->foreignId('shop_attribute_option_id')
                ->constrained('shop_attribute_options')
                ->cascadeOnDelete()
                ->name('attr_option_fk');
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['shop_product_attribute_value_id', 'shop_attribute_option_id'], 'product_attr_value_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_attribute_value_options');
    }
};
