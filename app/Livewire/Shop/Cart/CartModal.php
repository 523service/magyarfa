<?php

namespace App\Livewire\Shop\Cart;

use App\Models\Shop\Product;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class CartModal extends Component
{
    public bool $show = false;

    public ?int $productId = null;

    public string $productName = '';

    public string $productImage = '';

    public int $quantity = 0;

    public string $unitName = '';

    public float $price = 0;

    public int $cartItemCount = 0;

    public float $cartTotal = 0;

    /** @var Collection<int, Product> */
    public Collection $upsellProducts;

    public function mount(): void
    {
        $this->upsellProducts = collect();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[On('item-added-to-cart')]
    public function openModal(array $data, CartService $cartService): void
    {
        $this->productId = $data['productId'] ?? null;
        $this->productName = $data['productName'] ?? '';
        $this->productImage = $data['productImage'] ?? '';
        $this->quantity = $data['quantity'] ?? 0;
        $this->unitName = $data['unitName'] ?? 'db';
        $this->price = $data['price'] ?? 0;

        $this->cartItemCount = $cartService->getItemCount();
        $this->cartTotal = $cartService->getSubTotal();

        $this->loadUpsellProducts();

        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
    }

    public function goToCart(): mixed
    {
        $this->show = false;

        return $this->redirect(route('cart.index'), navigate: true);
    }

    protected function loadUpsellProducts(): void
    {
        $this->upsellProducts = Product::query()
            ->where('featured', true)
            ->where('is_visible', true)
            ->when($this->productId, fn ($query) => $query->where('id', '!=', $this->productId))
            ->inRandomOrder()
            ->limit(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.shop.cart.cart-modal');
    }
}
