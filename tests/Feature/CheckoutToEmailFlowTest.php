<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Mail\OrderConfirmation;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutToEmailFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_checkout_to_email_flow(): void
    {
        Mail::fake();
        // Don't fake events - we want the listener to actually run

        // Setup: Create product and add to cart
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => 5000]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $cartService = app(CartService::class);
        $cartService->addItem($product, 1);

        // Checkout data
        $checkoutData = [
            'email' => 'customer@example.com',
            'name' => 'Test Customer',
            'shipping' => [
                'street' => 'Test Street 1',
                'city' => 'Budapest',
                'zip' => '1234',
                'state' => null,
                'country' => 'HU',
            ],
            'billing' => [
                'street' => 'Test Street 1',
                'city' => 'Budapest',
                'zip' => '1234',
                'state' => null,
                'country' => 'HU',
                'billing_name' => 'Test Customer',
                'tax_number' => null,
            ],
            'shipping_method' => ShippingMethod::Courier,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'notes' => null,
        ];

        // Place order
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->placeOrder($checkoutData);

        // Assertions
        $this->assertNotNull($order);
        $this->assertEquals('customer@example.com', $order->email);

        // Email should be sent
        Mail::assertSent(OrderConfirmation::class, function ($mail) use ($order) {
            return $mail->hasTo('customer@example.com')
                && $mail->order->id === $order->id;
        });

        // Tracking record should exist
        $this->assertDatabaseHas('email_sends', [
            'shop_order_id' => $order->id,
            'recipient_email' => 'customer@example.com',
        ]);

        // PDF should be generated
        $pdfPath = storage_path("app/pdf/rendeles-{$order->number}.pdf");
        $this->assertFileExists($pdfPath);

        // Cart should be cleared
        $this->assertEquals(0, $cartService->getContent()->count());
    }
}
