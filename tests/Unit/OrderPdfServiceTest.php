<?php

namespace Tests\Unit;

use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use App\Services\OrderPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderPdfService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrderPdfService::class);
    }

    public function test_generates_pdf_for_order(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);

        $path = $this->service->generate($order);

        $this->assertFileExists($path);
        $this->assertStringContainsString('rendeles-' . $order->number . '.pdf', $path);
    }

    public function test_pdf_file_exists_after_generation(): void
    {
        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'pickup',
        ]);

        $path = $this->service->generate($order);

        $this->assertTrue(file_exists($path));
        $this->assertGreaterThan(0, filesize($path));
    }

    public function test_pdf_contains_hungarian_characters(): void
    {
        $unit = Unit::factory()->create(['name' => 'db']);
        $product = Product::factory()->create([
            'name' => 'Próba termék ÁÉÍÓÖŐÚÜŰáéíóöőúüű',
        ]);
        $product->units()->attach($unit->id, ['is_primary' => true]);

        $order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method' => 'courier',
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 1000,
            'sort' => 1,
        ]);

        $path = $this->service->generate($order);

        $this->assertFileExists($path);

        // Read PDF content
        $content = file_get_contents($path);
        $this->assertNotEmpty($content);
    }

    protected function tearDown(): void
    {
        // Clean up generated PDF files
        $pdfDir = storage_path('app/pdf');
        if (is_dir($pdfDir)) {
            $files = glob($pdfDir . '/*.pdf');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}
