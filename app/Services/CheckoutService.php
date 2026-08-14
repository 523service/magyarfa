<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Events\OrderPlaced;
use App\Models\Shop\Order;
use App\Models\Shop\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService,
    ) {}

    /**
     * Create a complete order from validated checkout data.
     *
     * @param  array{
     *     email: string,
     *     name: string,
     *     shipping: array{street: string, city: string, zip: string, state: ?string, country: string},
     *     billing: array{street: string, city: string, zip: string, state: ?string, country: string, billing_name: ?string, tax_number: ?string},
     *     shipping_method: ShippingMethod,
     *     payment_method: PaymentMethod,
     *     notes: ?string,
     * } $data
     */
    public function placeOrder(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $subtotal = $this->cartService->getSubTotal();
            $shippingPrice = $this->shippingService->calculatePrice(
                $data['shipping_method'],
                $subtotal
            );
            $totalPrice = $subtotal + $shippingPrice;

            $order = Order::create([
                'number' => $this->generateOrderNumber(),
                'email' => $data['email'],
                'status' => OrderStatus::New,
                'currency' => 'huf',
                'total_price' => $totalPrice,
                'shipping_price' => $shippingPrice,
                'shipping_method' => $data['shipping_method']->value,
                'notes' => $data['notes'],
            ]);

            $this->createOrderItems($order);
            $this->createOrderAddresses($order, $data);
            $this->createPayment($order, $data['payment_method'], $totalPrice);

            $this->cartService->clear();

            event(new OrderPlaced($order));

            return $order;
        });
    }

    protected function createOrderItems(Order $order): void
    {
        $sort = 1;

        foreach ($this->cartService->getContent() as $item) {
            OrderItem::create([
                'shop_order_id' => $order->id,
                'shop_product_id' => $item->attributes->product_id,
                'qty' => $item->quantity,
                'unit_name' => $item->attributes->unit_name,
                'secondary_qty' => $item->attributes->secondary_qty ?: null,
                'secondary_unit' => $item->attributes->secondary_unit ?: null,
                'unit_price' => $item->price,
                'sort' => $sort++,
            ]);
        }
    }

    protected function createOrderAddresses(Order $order, array $data): void
    {
        $order->addresses()->create([
            'type' => 'shipping',
            'name' => $data['name'],
            'street' => $data['shipping']['street'],
            'city' => $data['shipping']['city'],
            'zip' => $data['shipping']['zip'],
            'state' => $data['shipping']['state'],
            'country' => $data['shipping']['country'],
        ]);

        $order->addresses()->create([
            'type' => 'billing',
            'name' => $data['billing']['billing_name'] ?? $data['name'],
            'street' => $data['billing']['street'],
            'city' => $data['billing']['city'],
            'zip' => $data['billing']['zip'],
            'state' => $data['billing']['state'],
            'country' => $data['billing']['country'],
            'billing_name' => $data['billing']['billing_name'],
            'tax_number' => $data['billing']['tax_number'],
        ]);
    }

    protected function createPayment(Order $order, PaymentMethod $method, float $amount): void
    {
        $order->payments()->create([
            'reference' => $order->number . '-' . $method->value,
            'provider' => 'internal',
            'method' => $method->value,
            'amount' => $amount,
            'currency' => 'huf',
        ]);
    }

    protected function generateOrderNumber(): string
    {
        return 'OR' . strtoupper(Str::random(8));
    }
}
