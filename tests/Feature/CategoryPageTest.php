<?php

namespace Tests\Feature;

use App\Models\Shop\Brand;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_returns_200(): void
    {
        $category = Category::factory()->create(['is_visible' => true]);

        $this->withoutVite()->get('/kategoria/' . $category->slug)
            ->assertStatus(200);
    }

    public function test_category_page_shows_own_products(): void
    {
        $category = Category::factory()->create(['is_visible' => true]);
        $product = Product::factory()->create(['is_visible' => true]);
        $category->products()->attach($product->id);

        $response = $this->withoutVite()->get('/kategoria/' . $category->slug);

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('products'));
    }

    public function test_category_page_shows_subcategory_products(): void
    {
        $parent = Category::factory()->create(['is_visible' => true]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $childProduct = Product::factory()->create(['is_visible' => true]);
        $child->products()->attach($childProduct->id);

        $response = $this->withoutVite()->get('/kategoria/' . $parent->slug);

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $this->assertCount(1, $products);
        $this->assertEquals($childProduct->id, $products->first()->id);
    }

    public function test_category_page_shows_both_own_and_subcategory_products(): void
    {
        $parent = Category::factory()->create(['is_visible' => true]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $parentProduct = Product::factory()->create(['is_visible' => true]);
        $parent->products()->attach($parentProduct->id);

        $childProduct = Product::factory()->create(['is_visible' => true]);
        $child->products()->attach($childProduct->id);

        $response = $this->withoutVite()->get('/kategoria/' . $parent->slug);

        $response->assertStatus(200);
        $this->assertCount(2, $response->viewData('products'));
    }

    public function test_category_page_excludes_invisible_products(): void
    {
        $parent = Category::factory()->create(['is_visible' => true]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $invisible = Product::factory()->create(['is_visible' => false]);
        $child->products()->attach($invisible->id);

        $response = $this->withoutVite()->get('/kategoria/' . $parent->slug);

        $response->assertStatus(200);
        $this->assertCount(0, $response->viewData('products'));
    }

    public function test_category_page_excludes_invisible_subcategory_products(): void
    {
        $parent = Category::factory()->create(['is_visible' => true]);
        $child = Category::factory()->create([
            'is_visible' => false,
            'parent_id' => $parent->id,
        ]);

        $childProduct = Product::factory()->create(['is_visible' => true]);
        $child->products()->attach($childProduct->id);

        $response = $this->withoutVite()->get('/kategoria/' . $parent->slug);

        $response->assertStatus(200);
        // invisible child category is excluded from categoryIds
        $this->assertCount(0, $response->viewData('products'));
    }

    public function test_available_filters_include_subcategory_product_brands(): void
    {
        $parent = Category::factory()->create(['is_visible' => true]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $brand = Brand::factory()->create();
        $childProduct = Product::factory()->create([
            'is_visible' => true,
            'shop_brand_id' => $brand->id,
        ]);
        $child->products()->attach($childProduct->id);

        $response = $this->withoutVite()->get('/kategoria/' . $parent->slug);

        $response->assertStatus(200);
        $filters = $response->viewData('availableFilters');
        $this->assertTrue($filters['brands']->contains('id', $brand->id));
    }
}
