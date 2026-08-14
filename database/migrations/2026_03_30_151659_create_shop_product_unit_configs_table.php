<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_product_unit_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('base_unit_id')->constrained('shop_units');
            $table->foreignId('secondary_unit_id')->nullable()->constrained('shop_units')->nullOnDelete();
            $table->decimal('secondary_unit_qty', 10, 4)->nullable();
            $table->decimal('min_order_qty', 10, 4)->default(1);
            $table->decimal('order_step', 10, 4)->default(1);
            $table->decimal('price_per_base_unit', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('shop_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_unit_configs');
    }
};
