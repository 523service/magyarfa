<?php

namespace Tests\Feature;

use App\Livewire\Shop\Cart\AddToCartButton;
use App\Livewire\Shop\Cart\CartCounter;
use App\Livewire\Shop\Cart\CartModal;
use App\Livewire\Shop\Cart\CartPage;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Services\CartService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cart::clear();
    }

    protected function tearDown(): void
    {
        Cart::clear();
        parent::tearDown();
    }

    public function test_cart_counter_displays_zero_when_empty(): void
    {
        Livewire::test(CartCounter::class)
            ->assertSet('count', 0)
            ->assertSet('total', 0);
    }

    public function test_cart_counter_displays_correct_count_and_total(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1000]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $cartService->addItem($product, 2, $unit->id);

        Livewire::test(CartCounter::class)
            ->assertSet('count', 1)
            ->assertSet('total', 2000);
    }

    public function test_add_to_cart_button_mounts_with_product(): void
    {
        $unit = Unit::factory()->create(['name' => 'm2']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 10]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->assertSet('quantity', 1)
            ->assertSet('maxQuantity', 10)
            ->assertSet('unitId', $unit->id);
    }

    public function test_add_to_cart_button_can_increment_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 10]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->assertSet('quantity', 1)
            ->call('incrementQuantity')
            ->assertSet('quantity', 2)
            ->call('incrementQuantity')
            ->assertSet('quantity', 3);
    }

    public function test_add_to_cart_button_can_decrement_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 10]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->set('quantity', 5)
            ->call('decrementQuantity')
            ->assertSet('quantity', 4)
            ->call('decrementQuantity')
            ->assertSet('quantity', 3);
    }

    public function test_add_to_cart_button_cannot_go_below_one(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 10]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->assertSet('quantity', 1)
            ->call('decrementQuantity')
            ->assertSet('quantity', 1);
    }

    public function test_add_to_cart_button_cannot_exceed_max_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 3]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->set('quantity', 3)
            ->call('incrementQuantity')
            ->assertSet('quantity', 3);
    }

    public function test_add_to_cart_button_dispatches_events(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'qty' => 10, 'name' => 'Test Product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->assertDispatched('item-added-to-cart');
    }

    public function test_cart_modal_is_hidden_by_default(): void
    {
        Livewire::test(CartModal::class)
            ->assertSet('show', false);
    }

    public function test_cart_modal_opens_on_event(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 500, 'name' => 'Test Product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        Livewire::test(CartModal::class)
            ->dispatch('item-added-to-cart', [
                'productId' => $product->id,
                'productName' => 'Test Product',
                'productImage' => '',
                'quantity' => 2,
                'unitName' => 'db',
                'price' => 500,
                'rowId' => '1-1',
            ])
            ->assertSet('show', true)
            ->assertSet('productName', 'Test Product')
            ->assertSet('quantity', 2)
            ->assertSet('price', 500);
    }

    public function test_cart_modal_can_close(): void
    {
        Livewire::test(CartModal::class)
            ->set('show', true)
            ->call('closeModal')
            ->assertSet('show', false);
    }

    public function test_cart_page_shows_empty_cart_message(): void
    {
        Livewire::test(CartPage::class)
            ->assertSet('itemCount', 0)
            ->assertSee('A kosarad üres');
    }

    public function test_cart_page_displays_items(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1000, 'name' => 'Test Product', 'slug' => 'test-product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $cartService->addItem($product, 2, $unit->id);

        Livewire::test(CartPage::class)
            ->assertSet('itemCount', 1)
            ->assertSet('subTotal', 2000)
            ->assertSee('Test Product');
    }

    public function test_cart_page_can_increment_item_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1000, 'slug' => 'test-product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $result = $cartService->addItem($product, 1, $unit->id);

        Livewire::test(CartPage::class)
            ->assertSet('subTotal', 1000)
            ->call('incrementQuantity', $result['rowId'])
            ->assertSet('subTotal', 2000);
    }

    public function test_cart_page_can_decrement_item_quantity(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1000, 'slug' => 'test-product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $result = $cartService->addItem($product, 3, $unit->id);

        Livewire::test(CartPage::class)
            ->assertSet('subTotal', 3000)
            ->call('decrementQuantity', $result['rowId'])
            ->assertSet('subTotal', 2000);
    }

    public function test_cart_page_can_remove_item(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['price' => 1000, 'slug' => 'test-product']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $result = $cartService->addItem($product, 1, $unit->id);

        Livewire::test(CartPage::class)
            ->assertSet('itemCount', 1)
            ->call('removeItem', $result['rowId'])
            ->assertSet('itemCount', 0)
            ->assertDispatched('cart-updated');
    }

    public function test_cart_page_can_clear_cart(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product1 = Product::factory()->create(['price' => 1000, 'slug' => 'test-product-1']);
        $product2 = Product::factory()->create(['price' => 2000, 'slug' => 'test-product-2']);
        $product1->units()->attach($unit->id, ['is_primary' => true]);
        $product2->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $cartService->addItem($product1, 1, $unit->id);
        $cartService->addItem($product2, 1, $unit->id);

        Livewire::test(CartPage::class)
            ->assertSet('itemCount', 2)
            ->call('clearCart')
            ->assertSet('itemCount', 0)
            ->assertDispatched('cart-updated');
    }

    public function test_cart_page_route_is_accessible(): void
    {
        $response = $this->get('/kosar');

        $response->assertStatus(200);
        $response->assertSeeLivewire(CartPage::class);
    }
}
