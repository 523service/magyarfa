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
            $table->timestamp('ai_description_generated_at')->nullable()->after('seo_description');
            $table->timestamp('ai_description_reviewed_at')->nullable()->after('ai_description_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['ai_description_generated_at', 'ai_description_reviewed_at']);
        });
    }
};
