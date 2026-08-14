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
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->unsignedInteger('position')->default(0)->after('id');

            $table->dropColumn([
                'security_stock',
                'backorder',
                'weight_value',
                'weight_unit',
                'height_value',
                'height_unit',
                'width_value',
                'width_unit',
                'depth_value',
                'depth_unit',
                'volume_value',
                'volume_unit',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->dropColumn('position');
            $table->unsignedBigInteger('security_stock')->default(0);
            $table->boolean('backorder')->default(false);
            $table->decimal('weight_value', 10, 2)->nullable()->default(0.00)->unsigned();
            $table->string('weight_unit')->default('kg');
            $table->decimal('height_value', 10, 2)->nullable()->default(0.00)->unsigned();
            $table->string('height_unit')->default('cm');
            $table->decimal('width_value', 10, 2)->nullable()->default(0.00)->unsigned();
            $table->string('width_unit')->default('cm');
            $table->decimal('depth_value', 10, 2)->nullable()->default(0.00)->unsigned();
            $table->string('depth_unit')->default('cm');
            $table->decimal('volume_value', 10, 2)->nullable()->default(0.00)->unsigned();
            $table->string('volume_unit')->default('l');
        });
    }
};
