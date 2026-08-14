<?php

namespace Tests\Feature;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    {
        $this->withoutVite()->get('/')->assertStatus(200);
    }

    public function test_featured_category_count_includes_subcategory_products(): void
    {
        $parent = Category::factory()->create([
            'is_visible' => true,
            'is_featured' => true,
        ]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $parentProduct = Product::factory()->create(['is_visible' => true]);
        $parent->products()->attach($parentProduct->id);

        $childProduct = Product::factory()->create(['is_visible' => true]);
        $child->products()->attach($childProduct->id);

        $response = $this->withoutVite()->get('/');

        $response->assertStatus(200);
        $featured = $response->viewData('featuredCategories')->firstWhere('id', $parent->id);
        $this->assertEquals(2, $featured->products_count);
    }

    public function test_featured_category_count_excludes_invisible_products(): void
    {
        $parent = Category::factory()->create([
            'is_visible' => true,
            'is_featured' => true,
        ]);
        $child = Category::factory()->create([
            'is_visible' => true,
            'parent_id' => $parent->id,
        ]);

        $invisible = Product::factory()->create(['is_visible' => false]);
        $child->products()->attach($invisible->id);

        $response = $this->withoutVite()->get('/');

        $response->assertStatus(200);
        $featured = $response->viewData('featuredCategories')->firstWhere('id', $parent->id);
        $this->assertEquals(0, $featured->products_count);
    }
}
