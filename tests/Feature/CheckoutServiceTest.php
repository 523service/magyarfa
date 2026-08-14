<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Events\OrderPlaced;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Services\CartService;
use App\Services\CheckoutService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cart::clear();
    }

    protected function seedProduct(): Product
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . rand(1000, 9999),
            'sku' => 'SKU-' . rand(1000, 9999),
            'price' => 5000,
            'qty' => 100,
            'is_visible' => true,
            'published_at' => now(),
        ]);

        $unit = Unit::factory()->create(['name' => 'db']);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        return $product;
    }

    protected function addToCart(Product $product, int $qty = 1): void
    {
        $cartService = new CartService;
        $cartService->addItem($product, $qty);
    }

    protected function buildCheckoutData(
        ShippingMethod $shipping = ShippingMethod::Courier,
        PaymentMethod $payment = PaymentMethod::BankTransfer,
    ): array {
        return [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'shipping' => [
                'street' => 'Kossuth utca 10',
                'city' => 'Budapest',
                'zip' => '1234',
                'state' => null,
                'country' => 'HU',
            ],
            'billing' => [
                'street' => 'Kossuth utca 10',
                'city' => 'Budapest',
                'zip' => '1234',
                'state' => null,
                'country' => 'HU',
                'billing_name' => 'Test User',
                'tax_number' => null,
            ],
            'shipping_method' => $shipping,
            'payment_method' => $payment,
            'notes' => null,
        ];
    }

    public function test_place_order_creates_order_with_correct_status_and_currency(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('new', $order->status->value);
        $this->assertEquals('huf', $order->currency);
    }

    public function test_order_items_match_cart_contents(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product, 3);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $items = $order->items;
        $this->assertCount(1, $items);
        $this->assertEquals($product->id, $items[0]->shop_product_id);
        $this->assertEquals(3, $items[0]->qty);
        $this->assertEquals(5000, $items[0]->unit_price);
        $this->assertEquals('db', $items[0]->unit_name);
    }

    public function test_order_items_have_correct_sort_order(): void
    {
        $p1 = $this->seedProduct();
        $p2 = $this->seedProduct();
        $p2->slug = 'test-product-2-' . rand(1000, 9999);
        $p2->save();

        $this->addToCart($p1);
        $this->addToCart($p2);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $items = $order->items->sortBy('sort');
        $this->assertEquals(1, $items->first()->sort);
        $this->assertEquals(2, $items->last()->sort);
    }

    public function test_shipping_and_billing_addresses_are_created(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $addresses = $order->addresses;
        $this->assertCount(2, $addresses);
        $this->assertNotNull($addresses->firstWhere('type', 'shipping'));
        $this->assertNotNull($addresses->firstWhere('type', 'billing'));
    }

    public function test_shipping_address_data_is_correct(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $shipping = $order->addresses->firstWhere('type', 'shipping');
        $this->assertEquals('Kossuth utca 10', $shipping->street);
        $this->assertEquals('Budapest', $shipping->city);
        $this->assertEquals('1234', $shipping->zip);
        $this->assertEquals('Test User', $shipping->name);
    }

    public function test_billing_same_as_shipping_copies_address(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $shipping = $order->addresses->firstWhere('type', 'shipping');
        $billing = $order->addresses->firstWhere('type', 'billing');

        $this->assertEquals($shipping->street, $billing->street);
        $this->assertEquals($shipping->city, $billing->city);
        $this->assertEquals($shipping->zip, $billing->zip);
    }

    public function test_billing_with_tax_number(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $data = $this->buildCheckoutData();
        $data['billing'] = [
            'street' => 'Cím utca 5',
            'city' => 'Pécs',
            'zip' => '7600',
            'state' => null,
            'country' => 'HU',
            'billing_name' => 'Kovács Kft.',
            'tax_number' => 'HU12345678',
        ];

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($data);

        $billing = $order->addresses->firstWhere('type', 'billing');
        $this->assertEquals('Kovács Kft.', $billing->billing_name);
        $this->assertEquals('HU12345678', $billing->tax_number);
    }

    public function test_payment_record_is_created(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData(payment: PaymentMethod::CashOnDelivery));

        $payment = $order->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals('internal', $payment->provider);
        $this->assertEquals('cash_on_delivery', $payment->method);
        $this->assertEquals('huf', $payment->currency);
    }

    public function test_cart_is_cleared_after_order(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $service->placeOrder($this->buildCheckoutData());

        $cartService = new CartService;
        $this->assertTrue($cartService->isEmpty());
    }

    public function test_total_price_includes_shipping(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product); // 5000 Ft — below 15000 threshold

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData(shipping: ShippingMethod::Courier));

        $courierPrice = (int) config('shop.courier_price');
        $this->assertEquals(5000 + $courierPrice, $order->total_price);
        $this->assertEquals($courierPrice, $order->shipping_price);
    }

    public function test_total_price_with_free_shipping(): void
    {
        $product = $this->seedProduct();
        $threshold = (int) config('shop.free_shipping_threshold');
        $product->price = $threshold;
        $product->save();
        $this->addToCart($product); // >= threshold → ingyen

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData(shipping: ShippingMethod::Courier));

        $this->assertEquals(0, $order->shipping_price);
        $this->assertEquals($threshold, $order->total_price);
    }

    public function test_total_price_with_pickup(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product); // 5000 Ft

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData(shipping: ShippingMethod::Pickup));

        $this->assertEquals(0, $order->shipping_price);
        $this->assertEquals(5000, $order->total_price);
    }

    public function test_order_numbers_are_unique(): void
    {
        $product = $this->seedProduct();

        $service = $this->app->make(CheckoutService::class);

        $this->addToCart($product);
        $order1 = $service->placeOrder($this->buildCheckoutData());

        $this->addToCart($product);
        $order2 = $service->placeOrder($this->buildCheckoutData());

        $this->assertNotEquals($order1->number, $order2->number);
    }

    public function test_order_stores_email(): void
    {
        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        $this->assertEquals('test@example.com', $order->email);
        $this->assertDatabaseHas('shop_orders', [
            'id' => $order->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_order_placed_event_fired(): void
    {
        Event::fake();

        $product = $this->seedProduct();
        $this->addToCart($product);

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder($this->buildCheckoutData());

        Event::assertDispatched(OrderPlaced::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
}
