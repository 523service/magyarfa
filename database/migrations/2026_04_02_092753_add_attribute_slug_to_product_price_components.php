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
        Schema::table('product_price_components', function (Blueprint $table) {
            $table->string('attribute_slug', 100)->nullable()->after('quantity');
            $table->decimal('quantity', 10, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_price_components', function (Blueprint $table) {
            $table->dropColumn('attribute_slug');
            $table->decimal('quantity', 10, 4)->nullable(false)->change();
        });
    }
};
