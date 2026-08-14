<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_competitor_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('url');
            $table->string('competitor_name');
            $table->decimal('scraped_price', 10, 2)->nullable();
            $table->decimal('scraped_sale_price', 10, 2)->nullable();
            $table->string('scraped_image_url')->nullable();
            $table->longText('scraped_description')->nullable();
            $table->json('scraped_others')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->enum('scrape_status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('scrape_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_competitor_links');
    }
};
