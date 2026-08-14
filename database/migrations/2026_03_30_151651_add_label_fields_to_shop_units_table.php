<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_units', function (Blueprint $table): void {
            $table->string('label', 50)->default('')->after('slug');
            $table->string('label_short', 20)->default('')->after('label');
            $table->boolean('is_base_unit')->default(false)->after('label_short');
            $table->unsignedInteger('sort_order')->default(0)->after('is_base_unit');
        });
    }

    public function down(): void
    {
        Schema::table('shop_units', function (Blueprint $table): void {
            $table->dropColumn(['label', 'label_short', 'is_base_unit', 'sort_order']);
        });
    }
};
