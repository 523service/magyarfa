<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->string('manufacturer_website')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->dropColumn('manufacturer_website');
        });
    }
};
