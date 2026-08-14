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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("SET SESSION sql_mode=''");
        }

        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'pricing_mode')) {
                $table->string('pricing_mode')->default('manual')->after('price');
            }
            if (! Schema::hasColumn('shop_products', 'formula_type')) {
                $table->string('formula_type')->nullable()->after('pricing_mode');
            }
            if (! Schema::hasColumn('shop_products', 'material_price_id')) {
                $table->foreignId('material_price_id')
                    ->nullable()
                    ->after('formula_type')
                    ->constrained('material_base_prices')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('shop_products', 'system_template_id')) {
                $table->foreignId('system_template_id')
                    ->nullable()
                    ->after('material_price_id')
                    ->constrained('system_templates')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('shop_products', 'thickness_cm')) {
                $table->decimal('thickness_cm', 8, 2)->nullable()->after('system_template_id');
            }
            if (! Schema::hasColumn('shop_products', 'calculated_price')) {
                $table->decimal('calculated_price', 10, 2)->nullable()->after('thickness_cm');
            }
            if (! Schema::hasColumn('shop_products', 'price_calculated_at')) {
                $table->timestamp('price_calculated_at')->nullable()->after('calculated_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropForeign(['material_price_id']);
            $table->dropForeign(['system_template_id']);
            $table->dropColumn([
                'pricing_mode', 'formula_type', 'material_price_id',
                'system_template_id', 'thickness_cm', 'calculated_price', 'price_calculated_at',
            ]);
        });
    }
};
