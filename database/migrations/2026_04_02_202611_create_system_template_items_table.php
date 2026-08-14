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
        Schema::create('system_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_template_id')
                ->constrained('system_templates')
                ->cascadeOnDelete();
            $table->foreignId('material_price_id')
                ->constrained('material_base_prices')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('quantity_type'); // 'fixed' | 'product_thickness_cm'
            $table->decimal('quantity_value', 10, 4)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_template_items');
    }
};
