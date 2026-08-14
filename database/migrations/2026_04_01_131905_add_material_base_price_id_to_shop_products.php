<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable strict mode temporarily – the table has legacy 0000-00-00 datetime rows
        // that would cause MySQL strict mode to reject the FK constraint scan.
        // Only applicable to MySQL; SQLite does not support this statement.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("SET SESSION sql_mode=''");
        }

        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'material_base_price_id')) {
                $table->foreignId('material_base_price_id')
                    ->nullable()
                    ->after('price')
                    ->constrained('material_base_prices')
                    ->nullOnDelete();
            } else {
                // Column already exists (partially migrated) – just add the FK constraint
                $table->foreign('material_base_price_id')
                    ->references('id')
                    ->on('material_base_prices')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropForeign(['material_base_price_id']);
            $table->dropColumn('material_base_price_id');
        });
    }
};
