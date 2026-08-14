<?php

namespace App\Livewire\Shop\Checkout;

use App\Models\Shop\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Rendelés megerősítés - MagyarSzigetelés.hu')]
class OrderConfirmation extends Component
{
    public Order $order;

    public function mount(string $number): void
    {
        $this->order = Order::with('items', 'payments', 'addresses')
            ->where('number', $number)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.shop.checkout.order-confirmation');
    }
}
