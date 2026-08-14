<?php

namespace Tests\Feature;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_can_be_created(): void
    {
        $unit = Unit::factory()->create([
            'name' => 'Test Unit',
            'slug' => 'test-unit',
        ]);

        $this->assertDatabaseHas('shop_units', [
            'name' => 'Test Unit',
            'slug' => 'test-unit',
        ]);
    }

    public function test_unit_has_products_relationship(): void
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create();

        $product->units()->attach($unit->id, ['is_primary' => true]);

        $this->assertTrue($unit->products->contains($product));
        $this->assertTrue($product->units->contains($unit));
    }

    public function test_unit_has_categories_relationship(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();

        $category->units()->attach($unit->id, ['is_primary' => false]);

        $this->assertTrue($unit->categories->contains($category));
        $this->assertTrue($category->units->contains($unit));
    }

    public function test_product_display_unit_returns_primary_unit(): void
    {
        $unit = Unit::factory()->create(['name' => 'm2']);
        $product = Product::factory()->create();

        $product->units()->attach($unit->id, ['is_primary' => true]);

        $this->assertEquals('m2', $product->display_unit);
    }

    public function test_product_display_unit_falls_back_to_category_unit(): void
    {
        $unit = Unit::factory()->create(['name' => 'Raklap']);
        $category = Category::factory()->create();
        $product = Product::factory()->create();

        // Attach category to product
        $product->categories()->attach($category->id);

        // Attach unit to category as primary
        $category->units()->attach($unit->id, ['is_primary' => true]);

        // Product has no direct unit, should fall back to category
        $this->assertEquals('Raklap', $product->display_unit);
    }

    public function test_product_display_unit_returns_db_as_default(): void
    {
        $product = Product::factory()->create();

        // Product has no unit and no category unit
        $this->assertEquals('db', $product->display_unit);
    }

    public function test_product_primary_unit_takes_precedence_over_category(): void
    {
        $productUnit = Unit::factory()->create(['name' => 'm3']);
        $categoryUnit = Unit::factory()->create(['name' => 'Zsak']);
        $category = Category::factory()->create();
        $product = Product::factory()->create();

        // Attach category with unit
        $product->categories()->attach($category->id);
        $category->units()->attach($categoryUnit->id, ['is_primary' => true]);

        // Attach product's own primary unit
        $product->units()->attach($productUnit->id, ['is_primary' => true]);

        // Product's own unit should take precedence
        $this->assertEquals('m3', $product->display_unit);
    }

    public function test_unit_seeder_creates_all_units(): void
    {
        $this->seed(UnitSeeder::class);

        $expectedSlugs = [
            'db', 'm2', 'm3', 'raklap', 'bala', 'folyometer', 'zsak',
            'tekercs', 'szal', 'kaloda', 'vodor', 'par', 'csomag',
            'kg', 'karton', 'tabla', 'liter',
        ];

        foreach ($expectedSlugs as $slug) {
            $this->assertDatabaseHas('shop_units', ['slug' => $slug]);
        }

        $this->assertEquals(17, Unit::count());
    }

    public function test_pivot_stores_is_primary_for_products(): void
    {
        $unit1 = Unit::factory()->create();
        $unit2 = Unit::factory()->create();
        $product = Product::factory()->create();

        $product->units()->attach($unit1->id, ['is_primary' => true]);
        $product->units()->attach($unit2->id, ['is_primary' => false]);

        $this->assertTrue(
            $product->units()->wherePivot('is_primary', true)->first()->is($unit1)
        );
    }

    public function test_pivot_stores_is_primary_for_categories(): void
    {
        $unit1 = Unit::factory()->create();
        $unit2 = Unit::factory()->create();
        $category = Category::factory()->create();

        $category->units()->attach($unit1->id, ['is_primary' => true]);
        $category->units()->attach($unit2->id, ['is_primary' => false]);

        $this->assertTrue(
            $category->units()->wherePivot('is_primary', true)->first()->is($unit1)
        );
    }
}
