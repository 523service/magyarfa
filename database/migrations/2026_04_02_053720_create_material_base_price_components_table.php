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
        Schema::create('material_base_price_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_base_price_id')
                ->constrained('material_base_prices')
                ->cascadeOnDelete();
            $table->foreignId('component_mbp_id')
                ->constrained('material_base_prices')
                ->cascadeOnDelete();
            $table->decimal('quantity', 10, 4)->nullable();
            $table->string('attribute_slug', 100)->nullable();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_base_price_components');
    }
};
