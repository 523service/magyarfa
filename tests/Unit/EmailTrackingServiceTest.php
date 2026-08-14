<?php

namespace Tests\Unit;

use App\Models\Shop\Order;
use App\Services\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EmailTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EmailTrackingService::class);
    }

    public function test_creates_tracking_record(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);

        $emailSend = $this->service->createTracking($order, $order->email);

        $this->assertDatabaseHas('email_sends', [
            'shop_order_id' => $order->id,
            'recipient_email' => $order->email,
        ]);

        $this->assertNotNull($emailSend->tracking_token);
    }

    public function test_generates_unique_tracking_token(): void
    {
        $order1 = Order::factory()->create([
            'email' => 'test1@example.com',
            'shipping_method' => 'courier',
        ]);
        $order2 = Order::factory()->create([
            'email' => 'test2@example.com',
            'shipping_method' => 'pickup',
        ]);

        $send1 = $this->service->createTracking($order1, $order1->email);
        $send2 = $this->service->createTracking($order2, $order2->email);

        $this->assertNotEquals($send1->tracking_token, $send2->tracking_token);
        $this->assertEquals(32, strlen($send1->tracking_token));
    }

    public function test_records_email_open(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = $this->service->createTracking($order, $order->email);

        $this->assertNull($emailSend->opened_at);
        $this->assertEquals(0, $emailSend->open_count);

        $this->service->recordOpen($emailSend->tracking_token);

        $emailSend->refresh();
        $this->assertNotNull($emailSend->opened_at);
        $this->assertEquals(1, $emailSend->open_count);
    }

    public function test_increments_counters_on_multiple_opens(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = $this->service->createTracking($order, $order->email);

        $this->service->recordOpen($emailSend->tracking_token);
        $this->service->recordOpen($emailSend->tracking_token);
        $this->service->recordOpen($emailSend->tracking_token);

        $emailSend->refresh();
        $this->assertEquals(3, $emailSend->open_count);
    }

    public function test_records_link_click(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $emailSend = $this->service->createTracking($order, $order->email);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $targetUrl = 'https://example.com/order/123';
        $this->service->recordClick($emailSend->tracking_token, $targetUrl, $request);

        $this->assertDatabaseHas('email_link_clicks', [
            'email_send_id' => $emailSend->id,
            'url' => $targetUrl,
            'ip_address' => '127.0.0.1',
        ]);

        $emailSend->refresh();
        $this->assertEquals(1, $emailSend->click_count);
    }

    public function test_tracking_pixel_url_is_generated(): void
    {
        $token = 'test-token-123';
        $url = $this->service->getTrackingPixelUrl($token);

        $this->assertStringContainsString('/email/pixel/' . $token, $url);
    }

    public function test_tracked_link_url_is_generated(): void
    {
        $token = 'test-token-123';
        $targetUrl = 'https://example.com/order/123';
        $url = $this->service->getTrackedLinkUrl($token, $targetUrl);

        $this->assertStringContainsString('/email/click/' . $token, $url);

        // Parse URL and check query parameter
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        $this->assertEquals(base64_encode($targetUrl), $query['url'] ?? '');
    }
}
