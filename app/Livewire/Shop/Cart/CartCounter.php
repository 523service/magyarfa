<?php

namespace App\Livewire\Shop\Cart;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    public float $total = 0;

    public bool $compact = false;

    public function mount(CartService $cartService): void
    {
        $this->refreshCart($cartService);
    }

    #[On('cart-updated')]
    public function refreshCart(CartService $cartService): void
    {
        $this->count = $cartService->getItemCount();
        $this->total = $cartService->getSubTotal();
    }

    public function render()
    {
        return view('livewire.shop.cart.cart-counter');
    }
}
