<?php

namespace Tests\Feature;

use App\Enums\ShippingMethod;
use App\Services\ShippingService;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    protected ShippingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ShippingService;
    }

    public function test_courier_price_returns_configured_default(): void
    {
        $this->assertEquals(config('shop.courier_price'), $this->service->getCourierBasePrice());
    }

    public function test_free_threshold_returns_configured_value(): void
    {
        $this->assertEquals(config('shop.free_shipping_threshold'), $this->service->getFreeThreshold());
    }

    public function test_pickup_price_is_always_zero(): void
    {
        $price = $this->service->calculatePrice(ShippingMethod::Pickup, 0);

        $this->assertEquals(0, $price);
    }

    public function test_pickup_is_always_free_shipping(): void
    {
        $this->assertTrue($this->service->isFreeShipping(ShippingMethod::Pickup, 0));
        $this->assertTrue($this->service->isFreeShipping(ShippingMethod::Pickup, 50000));
    }

    public function test_courier_price_below_threshold(): void
    {
        $price = $this->service->calculatePrice(ShippingMethod::Courier, 5000);

        $this->assertEquals(config('shop.courier_price'), $price);
    }

    public function test_courier_is_free_at_exact_threshold(): void
    {
        $threshold = config('shop.free_shipping_threshold');
        $price = $this->service->calculatePrice(ShippingMethod::Courier, $threshold);

        $this->assertEquals(0, $price);
        $this->assertTrue($this->service->isFreeShipping(ShippingMethod::Courier, $threshold));
    }

    public function test_courier_is_free_above_threshold(): void
    {
        $threshold = config('shop.free_shipping_threshold');
        $price = $this->service->calculatePrice(ShippingMethod::Courier, $threshold + 1000);

        $this->assertEquals(0, $price);
        $this->assertTrue($this->service->isFreeShipping(ShippingMethod::Courier, $threshold + 1000));
    }

    public function test_courier_is_not_free_below_threshold(): void
    {
        $threshold = config('shop.free_shipping_threshold');
        $this->assertFalse($this->service->isFreeShipping(ShippingMethod::Courier, $threshold - 1));
    }
}
