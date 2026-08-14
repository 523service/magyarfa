<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Livewire\Shop\Checkout\CheckoutPage;
use App\Livewire\Shop\Checkout\OrderConfirmation;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
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

    protected function fillCart(): void
    {
        $product = $this->seedProduct();
        $cartService = new CartService;
        $cartService->addItem($product, 1);
    }

    // ---------------------------------------------------------------
    // Route & mount
    // ---------------------------------------------------------------
    public function test_checkout_redirects_when_cart_is_empty(): void
    {
        $response = $this->get('/rendeles');

        $response->assertRedirect('/kosar');
    }

    public function test_checkout_page_loads_with_items_in_cart(): void
    {
        $this->fillCart();

        $response = $this->get('/rendeles');

        $response->assertStatus(200);
        $response->assertSeeLivewire(CheckoutPage::class);
    }

    // ---------------------------------------------------------------
    // Auth step — guest
    // ---------------------------------------------------------------
    public function test_guest_sees_auth_step(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->assertSet('step', 'auth');
    }

    public function test_guest_continue_requires_name(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestEmail', 'test@example.com')
            ->set('guestName', '')
            ->call('continueAsGuest')
            ->assertHasErrors(['guestName']);
    }

    public function test_guest_continue_requires_email(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test User')
            ->set('guestEmail', '')
            ->call('continueAsGuest')
            ->assertHasErrors(['guestEmail']);
    }

    public function test_guest_continue_requires_valid_email(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test User')
            ->set('guestEmail', 'not-an-email')
            ->call('continueAsGuest')
            ->assertHasErrors(['guestEmail']);
    }

    public function test_guest_can_continue_with_valid_data(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test User')
            ->set('guestEmail', 'test@example.com')
            ->call('continueAsGuest')
            ->assertSet('step', 'address');
    }

    // ---------------------------------------------------------------
    // Auth step — login
    // ---------------------------------------------------------------
    public function test_login_with_valid_credentials_advances_to_address(): void
    {
        $this->fillCart();

        User::create([
            'name' => 'Logged In User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(CheckoutPage::class)
            ->set('authTab', 'login')
            ->set('loginEmail', 'user@example.com')
            ->set('loginPassword', 'password')
            ->call('loginAndContinue')
            ->assertSet('step', 'address')
            ->assertSet('authError', null);
    }

    public function test_login_with_wrong_password_shows_error(): void
    {
        $this->fillCart();

        User::create([
            'name' => 'Logged In User',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(CheckoutPage::class)
            ->set('authTab', 'login')
            ->set('loginEmail', 'user2@example.com')
            ->set('loginPassword', 'wrong')
            ->call('loginAndContinue')
            ->assertSet('step', 'auth')
            ->assertSet('authError', 'Hibás e-mail cím vagy jelszó.');
    }

    // ---------------------------------------------------------------
    // Auth step — register
    // ---------------------------------------------------------------
    public function test_register_creates_user_and_advances(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('authTab', 'register')
            ->set('registerName', 'New User')
            ->set('registerEmail', 'new@example.com')
            ->set('registerPassword', 'SecurePass123')
            ->set('registerPasswordConfirmation', 'SecurePass123')
            ->call('registerAndContinue')
            ->assertSet('step', 'address');

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'name' => 'New User']);
    }

    // ---------------------------------------------------------------
    // Auth step — logged-in user skips auth
    // ---------------------------------------------------------------
    public function test_logged_in_user_skips_auth_step(): void
    {
        $this->fillCart();

        $user = User::create([
            'name' => 'Logged In',
            'email' => 'logged@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->assertSet('step', 'address');
    }

    // ---------------------------------------------------------------
    // Address step
    // ---------------------------------------------------------------
    public function test_address_step_validates_shipping_fields(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test')
            ->set('guestEmail', 'test@example.com')
            ->call('continueAsGuest')
            ->set('shippingStreet', '')
            ->set('shippingZip', '')
            ->set('shippingCity', '')
            ->call('continueToShipping')
            ->assertHasErrors(['shippingStreet', 'shippingZip', 'shippingCity']);
    }

    public function test_address_step_advances_with_valid_data(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test')
            ->set('guestEmail', 'test@example.com')
            ->call('continueAsGuest')
            ->set('shippingStreet', 'Utca 1')
            ->set('shippingZip', '1234')
            ->set('shippingCity', 'Budapest')
            ->call('continueToShipping')
            ->assertSet('step', 'shipping');
    }

    // ---------------------------------------------------------------
    // Shipping step
    // ---------------------------------------------------------------
    public function test_shipping_price_updates_on_method_change(): void
    {
        $this->fillCart(); // 5000 Ft — below threshold

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test')
            ->set('guestEmail', 'test@example.com')
            ->call('continueAsGuest')
            ->set('shippingStreet', 'Utca 1')
            ->set('shippingZip', '1234')
            ->set('shippingCity', 'Budapest')
            ->call('continueToShipping')
            ->set('shippingMethod', 'courier')
            ->assertSet('shippingPrice', (int) config('shop.courier_price'))
            ->set('shippingMethod', 'pickup')
            ->assertSet('shippingPrice', 0);
    }

    public function test_shipping_price_is_zero_above_threshold(): void
    {
        $product = $this->seedProduct();
        $product->price = (int) config('shop.free_shipping_threshold');
        $product->save();
        $cartService = new CartService;
        $cartService->addItem($product, 1);

        Livewire::test(CheckoutPage::class)
            ->set('guestName', 'Test')
            ->set('guestEmail', 'test@example.com')
            ->call('continueAsGuest')
            ->set('shippingStreet', 'Utca 1')
            ->set('shippingZip', '1234')
            ->set('shippingCity', 'Budapest')
            ->call('continueToShipping')
            ->set('shippingMethod', 'courier')
            ->assertSet('shippingPrice', 0);
    }

    // ---------------------------------------------------------------
    // Full happy path
    // ---------------------------------------------------------------
    public function test_full_guest_checkout_happy_path(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutPage::class)
            // Step 1: guest
            ->set('guestName', 'Kovács János')
            ->set('guestEmail', 'kovacs@example.com')
            ->call('continueAsGuest')
            ->assertSet('step', 'address')
            // Step 2: address
            ->set('shippingStreet', 'Kossuth utca 10')
            ->set('shippingZip', '1234')
            ->set('shippingCity', 'Budapest')
            ->call('continueToShipping')
            ->assertSet('step', 'shipping')
            // Step 3: shipping
            ->set('shippingMethod', 'courier')
            ->call('continueToPayment')
            ->assertSet('step', 'payment')
            ->assertSet('shippingPrice', (int) config('shop.courier_price'))
            // Step 4: place order
            ->set('paymentMethod', 'bank_transfer')
            ->call('placeOrder');

        $this->assertCount(1, Order::all());

        $order = Order::first();
        $courierPrice = (int) config('shop.courier_price');
        $this->assertEquals('new', $order->status->value);
        $this->assertEquals('huf', $order->currency);
        $this->assertEquals($courierPrice, $order->shipping_price);
        $this->assertEquals(5000 + $courierPrice, $order->total_price);
        $this->assertCount(2, $order->addresses);
        $this->assertCount(1, $order->payments);
    }

    public function test_full_logged_in_checkout_happy_path(): void
    {
        $this->fillCart();

        $user = User::create([
            'name' => 'Logged User',
            'email' => 'logged2@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(CheckoutPage::class)
            ->assertSet('step', 'address')
            // Step 2: address (no saved addresses → inline)
            ->set('shippingStreet', 'Nagy utca 5')
            ->set('shippingZip', '5678')
            ->set('shippingCity', 'Pécs')
            ->call('continueToShipping')
            ->assertSet('step', 'shipping')
            // Step 3
            ->set('shippingMethod', 'pickup')
            ->call('continueToPayment')
            ->assertSet('step', 'payment')
            ->assertSet('shippingPrice', 0)
            // Step 4
            ->set('paymentMethod', 'cash_on_delivery')
            ->call('placeOrder');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, $order->shipping_price);
        $this->assertEquals('cash_on_delivery', $order->payments->first()->method);
    }

    // ---------------------------------------------------------------
    // Order confirmation
    // ---------------------------------------------------------------
    public function test_order_confirmation_page_displays_order(): void
    {
        $this->fillCart();

        $service = $this->app->make(CheckoutService::class);
        $order = $service->placeOrder([
            'email' => 'test@example.com',
            'name' => 'Test',
            'shipping' => ['street' => 'Utca 1', 'city' => 'BP', 'zip' => '1111', 'state' => null, 'country' => 'HU'],
            'billing' => ['street' => 'Utca 1', 'city' => 'BP', 'zip' => '1111', 'state' => null, 'country' => 'HU', 'billing_name' => 'Test', 'tax_number' => null],
            'shipping_method' => ShippingMethod::Pickup,
            'payment_method' => PaymentMethod::BankTransfer,
            'notes' => null,
        ]);

        $response = $this->get("/rendeles/megerosites/{$order->number}");

        $response->assertStatus(200);
        $response->assertSeeLivewire(OrderConfirmation::class);
        $response->assertSee($order->number);
    }

    public function test_order_confirmation_returns_404_for_invalid_number(): void
    {
        $response = $this->get('/rendeles/megerosites/INVALID99');

        $response->assertStatus(404);
    }
}
