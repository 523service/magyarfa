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
        Schema::table('shop_order_items', function (Blueprint $table): void {
            $table->decimal('secondary_qty', 10, 4)->nullable()->after('unit_name');
            $table->string('secondary_unit')->nullable()->after('secondary_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_order_items', function (Blueprint $table): void {
            $table->dropColumn(['secondary_qty', 'secondary_unit']);
        });
    }
};
