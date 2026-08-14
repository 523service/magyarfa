<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use App\Mail\StoreOrderNotification;
use App\Models\Shop\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_after_order_placed(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertSent(OrderConfirmation::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    public function test_email_contains_pdf_attachment(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertSent(OrderConfirmation::class, function ($mail) {
            // Check that attachments exist via the attachments() method
            $attachments = $mail->attachments();

            return count($attachments) > 0;
        });
    }

    public function test_email_subject_is_correct_hungarian(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertSent(OrderConfirmation::class, function ($mail) use ($order) {
            $envelope = $mail->envelope();

            return $envelope->subject === 'Rendelés visszaigazolás: ' . $order->number;
        });
    }

    public function test_store_notification_sent_to_store_email(): void
    {
        Mail::fake();
        config(['shop.store_email' => 'store@example.com']);

        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertSent(StoreOrderNotification::class, function ($mail) {
            return $mail->hasTo('store@example.com');
        });
    }

    public function test_store_notification_subject_is_correct(): void
    {
        Mail::fake();
        config(['shop.store_email' => 'store@example.com']);

        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertSent(StoreOrderNotification::class, function ($mail) use ($order) {
            return $mail->envelope()->subject === 'Új rendelés érkezett: ' . $order->number;
        });
    }

    public function test_store_notification_not_sent_when_store_email_empty(): void
    {
        Mail::fake();
        config(['shop.store_email' => '']);

        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertNotSent(StoreOrderNotification::class);
    }

    public function test_no_email_sent_if_order_has_no_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'email' => null,
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        Mail::assertNothingSent();
    }

    public function test_tracking_record_created(): void
    {
        $order = Order::factory()->create([
            'email' => 'customer@example.com',
            'shipping_method' => 'courier',
        ]);

        event(new OrderPlaced($order));

        $this->assertDatabaseHas('email_sends', [
            'shop_order_id' => $order->id,
            'recipient_email' => 'customer@example.com',
        ]);
    }
}
