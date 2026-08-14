<?php

namespace App\Livewire\Shop\Cart;

use App\Services\CartService;
use Darryldecode\Cart\CartCollection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Kosár - MagyarSzigeteles.hu')]
class CartPage extends Component
{
    public CartCollection $items;

    public float $subTotal = 0;

    public float $total = 0;

    public int $itemCount = 0;

    public function mount(CartService $cartService): void
    {
        $this->refreshCart($cartService);
    }

    #[On('cart-updated')]
    public function refreshCart(CartService $cartService): void
    {
        $this->items = $cartService->getContent();
        $this->subTotal = $cartService->getSubTotal();
        $this->total = $cartService->getTotal();
        $this->itemCount = $cartService->getItemCount();
    }

    public function updateQuantity(string $rowId, int $quantity, CartService $cartService): void
    {
        if ($quantity < 1) {
            $this->removeItem($rowId, $cartService);

            return;
        }

        $cartService->updateQuantity($rowId, $quantity);
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function incrementQuantity(string $rowId, CartService $cartService): void
    {
        $item = $cartService->getItem($rowId);
        if ($item) {
            $this->updateQuantity($rowId, $item->quantity + 1, $cartService);
        }
    }

    public function decrementQuantity(string $rowId, CartService $cartService): void
    {
        $item = $cartService->getItem($rowId);
        if ($item && $item->quantity > 1) {
            $this->updateQuantity($rowId, $item->quantity - 1, $cartService);
        }
    }

    public function removeItem(string $rowId, CartService $cartService): void
    {
        $cartService->removeItem($rowId);
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function clearCart(CartService $cartService): void
    {
        $cartService->clear();
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.shop.cart.cart-page');
    }
}
