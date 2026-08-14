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
        Schema::table('shop_order_addresses', function (Blueprint $table) {
            $table->string('type')->default('shipping')->after('addressable_id');
            $table->string('name')->nullable()->after('zip');
            $table->string('billing_name')->nullable()->after('name');
            $table->string('tax_number')->nullable()->after('billing_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_order_addresses', function (Blueprint $table) {
            $table->dropColumn(['type', 'name', 'billing_name', 'tax_number']);
        });
    }
};
