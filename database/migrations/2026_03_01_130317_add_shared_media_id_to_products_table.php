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
        Schema::table('shop_products', function (Blueprint $table) {
            $table->foreignId('shared_media_id')->nullable()->after('id')
                ->constrained('media')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropForeign('products_shared_media_id_foreign');
            $table->dropIndex('products_shared_media_id_foreign');
            $table->dropColumn('shared_media_id');
        });
    }
};
