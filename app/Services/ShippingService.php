<?php

namespace App\Services;

use App\Enums\ShippingMethod;

class ShippingService
{
    /**
     * Calculate shipping cost for the given method and cart subtotal.
     */
    public function calculatePrice(ShippingMethod $method, float $subtotal): int
    {
        return match ($method) {
            ShippingMethod::Pickup => 0,
            ShippingMethod::Courier => $this->courierCost($subtotal),
        };
    }

    /**
     * Whether free shipping applies for the given method and subtotal.
     */
    public function isFreeShipping(ShippingMethod $method, float $subtotal): bool
    {
        return match ($method) {
            ShippingMethod::Pickup => true,
            ShippingMethod::Courier => $subtotal >= $this->getFreeThreshold(),
        };
    }

    /**
     * The configured courier base price (Ft).
     */
    public function getCourierBasePrice(): int
    {
        return (int) config('shop.courier_price');
    }

    /**
     * The subtotal threshold above which courier shipping is free (Ft).
     */
    public function getFreeThreshold(): int
    {
        return (int) config('shop.free_shipping_threshold');
    }

    protected function courierCost(float $subtotal): int
    {
        if ($subtotal >= $this->getFreeThreshold()) {
            return 0;
        }

        return $this->getCourierBasePrice();
    }
}
