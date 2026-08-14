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
        Schema::table('shop_competitor_links', function (Blueprint $table) {
            $table->longText('scraped_short_description')->nullable()->after('scraped_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_competitor_links', function (Blueprint $table) {
            $table->dropColumn('scraped_short_description');
        });
    }
};
