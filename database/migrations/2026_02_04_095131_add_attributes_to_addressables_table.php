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
        Schema::table('addressables', function (Blueprint $table) {
            $table->string('type')->default('shipping')->after('addressable_type');
            $table->boolean('is_default')->default(false)->after('type');
            $table->string('label')->nullable()->after('is_default');
            $table->string('billing_name')->nullable()->after('label');
            $table->string('tax_number')->nullable()->after('billing_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addressables', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_default', 'label', 'billing_name', 'tax_number', 'created_at', 'updated_at']);
        });
    }
};
