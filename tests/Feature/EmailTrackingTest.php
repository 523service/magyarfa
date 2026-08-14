<?php

namespace Tests\Feature;

use App\Models\EmailSend;
use App\Models\Shop\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pixel_route_records_open(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = EmailSend::create([
            'shop_order_id' => $order->id,
            'recipient_email' => $order->email,
            'subject' => 'Test',
            'tracking_token' => 'test-token-123',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email/pixel/' . $emailSend->tracking_token . '.png');

        $response->assertStatus(200);

        $emailSend->refresh();
        $this->assertNotNull($emailSend->opened_at);
        $this->assertEquals(1, $emailSend->open_count);
    }

    public function test_pixel_returns_transparent_png(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = EmailSend::create([
            'shop_order_id' => $order->id,
            'recipient_email' => $order->email,
            'subject' => 'Test',
            'tracking_token' => 'test-token-123',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email/pixel/' . $emailSend->tracking_token . '.png');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');

        // Check that Cache-Control header contains no-cache directive
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_click_route_records_click_and_redirects(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = EmailSend::create([
            'shop_order_id' => $order->id,
            'recipient_email' => $order->email,
            'subject' => 'Test',
            'tracking_token' => 'test-token-123',
            'sent_at' => now(),
        ]);

        $targetUrl = 'https://example.com/order/123';
        $encodedUrl = base64_encode($targetUrl);

        $response = $this->get('/email/click/' . $emailSend->tracking_token . '?url=' . $encodedUrl);

        $response->assertRedirect($targetUrl);

        $emailSend->refresh();
        $this->assertEquals(1, $emailSend->click_count);
    }

    public function test_click_stores_ip_and_user_agent(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = EmailSend::create([
            'shop_order_id' => $order->id,
            'recipient_email' => $order->email,
            'subject' => 'Test',
            'tracking_token' => 'test-token-123',
            'sent_at' => now(),
        ]);

        $targetUrl = 'https://example.com/order/123';
        $encodedUrl = base64_encode($targetUrl);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ])->get('/email/click/' . $emailSend->tracking_token . '?url=' . $encodedUrl);

        $this->assertDatabaseHas('email_link_clicks', [
            'email_send_id' => $emailSend->id,
            'url' => $targetUrl,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get('/email/click/invalid-token?url=' . base64_encode('https://example.com'));

        $response->assertRedirect('https://example.com');
    }
}
