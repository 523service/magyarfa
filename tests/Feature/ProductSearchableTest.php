<?php

namespace Tests\Feature;

use App\Models\Shop\Brand;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_returns_expected_keys(): void
    {
        $brand = Brand::factory()->create(['name' => 'TestBrand']);
        $category = Category::factory()->create(['name' => 'TestCategory', 'is_visible' => true]);
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'SKU-001',
            'price' => 5000,
            'is_visible' => true,
            'featured' => false,
            'shop_brand_id' => $brand->id,
        ]);
        $product->categories()->attach($category->id);
        $product->load(['brand', 'categories', 'media']);

        $array = $product->toSearchableArray();

        $this->assertArrayHasKey('objectID', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('sku', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('price_formatted', $array);
        $this->assertArrayHasKey('brand_name', $array);
        $this->assertArrayHasKey('category_names', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals('TestBrand', $array['brand_name']);
        $this->assertContains('TestCategory', $array['category_names']);
    }

    public function test_to_searchable_array_includes_formatted_price(): void
    {
        $product = Product::factory()->create([
            'price' => 12500,
            'is_visible' => true,
            'slug' => 'formatted-price-product',
        ]);
        $product->load(['brand', 'categories', 'media']);

        $array = $product->toSearchableArray();

        $this->assertStringEndsWith(' Ft', $array['price_formatted']);
        $this->assertIsString($array['price_formatted']);
    }

    public function test_invisible_product_is_not_searchable(): void
    {
        $product = Product::factory()->create(['is_visible' => false]);

        $this->assertFalse($product->shouldBeSearchable());
    }

    public function test_visible_product_is_searchable(): void
    {
        $product = Product::factory()->create(['is_visible' => true]);

        $this->assertTrue($product->shouldBeSearchable());
    }

    public function test_make_all_searchable_using_eager_loads_relationships(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create(['is_visible' => true]);

        Product::factory()->count(3)->create([
            'shop_brand_id' => $brand->id,
            'is_visible' => true,
        ])->each(fn ($p) => $p->categories()->attach($category->id));

        $query = $this->app->make(Product::class)->newQuery();
        $query = (new Product)->makeAllSearchableUsing($query);

        $eagerLoads = array_keys($query->getEagerLoads());

        $this->assertContains('brand', $eagerLoads);
        $this->assertContains('categories', $eagerLoads);
        $this->assertContains('media', $eagerLoads);
    }
}
