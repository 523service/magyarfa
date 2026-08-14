<?php

namespace Tests\Feature;

use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Services\CartService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
        Cart::clear();
    }

    protected function tearDown(): void
    {
        Cart::clear();
        parent::tearDown();
    }

    public function test_can_add_item_to_cart(): void
    {
        $unit = Unit::factory()->create(['name' => 'm2']);
        $product = Product::factory()->create(['price' => 1000]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 2, $unit->id);

        $this->assertArrayHasKey('rowId', $result);
        $this->assertArrayHasKey('item', $result);
        $this->assertEquals(1, $this->cartService->getItemCount());
        $this->assertEquals(2000, $this->cartService->getSubTotal());
    }

    public function test_can_add_item_without_specifying_unit(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 3);

        $this->assertNotNull($result['item']);
        $this->assertEquals($unit->id, $result['item']->attributes->unit_id);
    }

    public function test_cart_generates_unique_row_id_per_product_unit_combination(): void
    {
        $unit1 = Unit::factory()->create(['name' => 'm2']);
        $unit2 = Unit::factory()->create(['name' => 'm3']);
        $product = Product::factory()->create(['price' => 1000]);
        $product->units()->attach($unit1->id, ['is_primary' => true]);
        $product->units()->attach($unit2->id, ['is_primary' => false]);

        $result1 = $this->cartService->addItem($product, 1, $unit1->id);
        $result2 = $this->cartService->addItem($product, 1, $unit2->id);

        $this->assertNotEquals($result1['rowId'], $result2['rowId']);
        $this->assertEquals(2, $this->cartService->getItemCount());
    }

    public function test_adding_same_product_unit_increases_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'm2']);
        $product = Product::factory()->create(['price' => 1000]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $this->cartService->addItem($product, 2, $unit->id);
        $this->cartService->addItem($product, 3, $unit->id);

        $this->assertEquals(1, $this->cartService->getItemCount());
        $this->assertEquals(5, $this->cartService->getTotalQuantity());
    }

    public function test_can_update_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 100]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 2, $unit->id);

        $updated = $this->cartService->updateQuantity($result['rowId'], 5);

        $this->assertTrue($updated);
        $this->assertEquals(5, $this->cartService->getTotalQuantity());
        $this->assertEquals(500, $this->cartService->getSubTotal());
    }

    public function test_update_quantity_returns_false_for_invalid_row(): void
    {
        $updated = $this->cartService->updateQuantity('invalid-row-id', 5);

        $this->assertFalse($updated);
    }

    public function test_can_remove_item(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 100]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 2, $unit->id);

        $this->assertEquals(1, $this->cartService->getItemCount());

        $removed = $this->cartService->removeItem($result['rowId']);

        $this->assertTrue($removed);
        $this->assertEquals(0, $this->cartService->getItemCount());
        $this->assertTrue($this->cartService->isEmpty());
    }

    public function test_can_clear_cart(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 200]);
        $product1->units()->attach($unit->id, ['is_primary' => true]);
        $product2->units()->attach($unit->id, ['is_primary' => true]);

        $this->cartService->addItem($product1, 1, $unit->id);
        $this->cartService->addItem($product2, 1, $unit->id);

        $this->assertEquals(2, $this->cartService->getItemCount());

        $cleared = $this->cartService->clear();

        $this->assertTrue($cleared);
        $this->assertTrue($this->cartService->isEmpty());
        $this->assertEquals(0, $this->cartService->getSubTotal());
    }

    public function test_get_content_returns_cart_collection(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 100]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $this->cartService->addItem($product, 2, $unit->id);

        $content = $this->cartService->getContent();

        $this->assertCount(1, $content);
    }

    public function test_cart_stores_product_attributes(): void
    {
        $unit = Unit::factory()->create(['name' => 'm2']);
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 1000,
            'slug' => 'test-product',
        ]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 1, $unit->id);

        $item = $result['item'];
        $this->assertEquals($product->id, $item->attributes->product_id);
        $this->assertEquals($unit->id, $item->attributes->unit_id);
        $this->assertEquals('m2', $item->attributes->unit_name);
        $this->assertEquals('test-product', $item->attributes->slug);
    }

    public function test_can_check_if_item_exists(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 100]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 1, $unit->id);

        $this->assertTrue($this->cartService->hasItem($result['rowId']));
        $this->assertFalse($this->cartService->hasItem('non-existent'));
    }

    public function test_can_get_specific_item(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 100, 'name' => 'Test Product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $result = $this->cartService->addItem($product, 1, $unit->id);

        $item = $this->cartService->getItem($result['rowId']);

        $this->assertNotNull($item);
        $this->assertEquals('Test Product', $item->name);
    }

    public function test_get_total_equals_subtotal_without_conditions(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1500]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $this->cartService->addItem($product, 2, $unit->id);

        $this->assertEquals($this->cartService->getSubTotal(), $this->cartService->getTotal());
        $this->assertEquals(3000, $this->cartService->getTotal());
    }
}
