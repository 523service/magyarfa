<?php

namespace App\Livewire\Shop\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\Address;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Rendelés - MagyarSzigetelés.hu')]
class CheckoutPage extends Component
{
    // ---------------------------------------------------------------
    // Step state
    // ---------------------------------------------------------------
    public string $step = 'auth';

    // ---------------------------------------------------------------
    // Auth step
    // ---------------------------------------------------------------
    public string $authTab = 'guest';

    #[Validate('required|string|max:255')]
    public string $guestName = '';

    #[Validate('required|string|email|max:255')]
    public string $guestEmail = '';

    #[Validate('required|string|email|max:255')]
    public string $loginEmail = '';

    #[Validate('required|string|min:1')]
    public string $loginPassword = '';

    #[Validate('required|string|max:255')]
    public string $registerName = '';

    #[Validate('required|string|email|max:255|unique:users,email')]
    public string $registerEmail = '';

    #[Validate('required|string|min:8')]
    public string $registerPassword = '';

    #[Validate('required|string')]
    public string $registerPasswordConfirmation = '';

    public ?string $authError = null;

    // ---------------------------------------------------------------
    // Address step — shipping
    // ---------------------------------------------------------------
    public ?int $selectedShippingAddressId = null;

    public bool $addingNewShippingAddress = false;

    #[Validate('required|string|max:255')]
    public string $shippingStreet = '';

    #[Validate('required|string|max:20')]
    public string $shippingZip = '';

    #[Validate('required|string|max:100')]
    public string $shippingCity = '';

    #[Validate('nullable|string|max:100')]
    public string $shippingState = '';

    #[Validate('required|string|max:2')]
    public string $shippingCountry = 'HU';

    // ---------------------------------------------------------------
    // Address step — billing
    // ---------------------------------------------------------------
    public bool $billingIsSameAsShipping = true;

    public ?int $selectedBillingAddressId = null;

    public bool $addingNewBillingAddress = false;

    #[Validate('required_if:billingIsSameAsShipping,false|nullable|string|max:255')]
    public string $billingStreet = '';

    #[Validate('required_if:billingIsSameAsShipping,false|nullable|string|max:20')]
    public string $billingZip = '';

    #[Validate('required_if:billingIsSameAsShipping,false|nullable|string|max:100')]
    public string $billingCity = '';

    #[Validate('nullable|string|max:100')]
    public string $billingState = '';

    #[Validate('required_if:billingIsSameAsShipping,false|nullable|string|max:2')]
    public string $billingCountry = 'HU';

    #[Validate('nullable|string|max:255')]
    public string $billingName = '';

    #[Validate('nullable|string|max:50')]
    public string $billingTaxNumber = '';

    // ---------------------------------------------------------------
    // Shipping step
    // ---------------------------------------------------------------
    public string $shippingMethod = 'courier';

    // ---------------------------------------------------------------
    // Payment step
    // ---------------------------------------------------------------
    public string $paymentMethod = 'bank_transfer';

    public string $notes = '';

    // ---------------------------------------------------------------
    // Computed totals
    // ---------------------------------------------------------------
    public float $subTotal = 0;

    public int $shippingPrice = 0;

    public float $totalPrice = 0;

    public int $itemCount = 0;

    public int $freeShippingThreshold = 0;

    public int $courierPrice = 0;

    // ---------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------
    public function mount(CartService $cartService): void
    {
        if ($cartService->isEmpty()) {
            $this->redirect(route('cart.index'));

            return;
        }

        $this->freeShippingThreshold = (int) config('shop.free_shipping_threshold');
        $this->courierPrice = (int) config('shop.courier_price');
        $this->refreshTotals($cartService);

        if (Auth::check()) {
            $this->step = 'address';
            $this->initLoggedInAddresses();
        }
    }

    // ---------------------------------------------------------------
    // Auth step actions
    // ---------------------------------------------------------------
    public function continueAsGuest(): void
    {
        $this->validateOnly('guestName');
        $this->validateOnly('guestEmail');

        $this->step = 'address';
    }

    public function loginAndContinue(): void
    {
        $this->validateOnly('loginEmail');
        $this->validateOnly('loginPassword');

        if (! Auth::attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword])) {
            $this->authError = 'Hibás e-mail cím vagy jelszó.';

            return;
        }

        $this->authError = null;
        $this->step = 'address';
        $this->initLoggedInAddresses();
    }

    public function registerAndContinue(): void
    {
        $this->validateOnly('registerName');
        $this->validateOnly('registerEmail');
        $this->validateOnly('registerPassword');
        $this->validateOnly('registerPasswordConfirmation');

        if ($this->registerPassword !== $this->registerPasswordConfirmation) {
            $this->addError('registerPassword', 'A jelszó megerősítése nem egyezik.');

            return;
        }

        $user = User::create([
            'name' => $this->registerName,
            'email' => $this->registerEmail,
            'password' => Hash::make($this->registerPassword),
        ]);

        Auth::login($user);

        $this->step = 'address';
        $this->initLoggedInAddresses();
    }

    // ---------------------------------------------------------------
    // Address step actions
    // ---------------------------------------------------------------
    public function selectShippingAddress(int $addressId): void
    {
        $this->selectedShippingAddressId = $addressId;
        $this->addingNewShippingAddress = false;
        $this->loadShippingAddressFields($addressId);
    }

    public function addNewShippingAddress(): void
    {
        $this->addingNewShippingAddress = true;
        $this->selectedShippingAddressId = null;
        $this->shippingStreet = '';
        $this->shippingZip = '';
        $this->shippingCity = '';
        $this->shippingState = '';
        $this->shippingCountry = 'HU';
    }

    public function selectBillingAddress(int $addressId): void
    {
        $this->selectedBillingAddressId = $addressId;
        $this->addingNewBillingAddress = false;
        $this->loadBillingAddressFields($addressId);
    }

    public function addNewBillingAddress(): void
    {
        $this->addingNewBillingAddress = true;
        $this->selectedBillingAddressId = null;
        $this->billingStreet = '';
        $this->billingZip = '';
        $this->billingCity = '';
        $this->billingState = '';
        $this->billingCountry = 'HU';
        $this->billingName = '';
        $this->billingTaxNumber = '';
    }

    public function continueToShipping(CartService $cartService): void
    {
        $rules = [
            'shippingStreet' => 'required|string|max:255',
            'shippingZip' => 'required|string|max:20',
            'shippingCity' => 'required|string|max:100',
            'shippingCountry' => 'required|string|max:2',
        ];

        if (! $this->billingIsSameAsShipping) {
            $rules['billingStreet'] = 'required|string|max:255';
            $rules['billingZip'] = 'required|string|max:20';
            $rules['billingCity'] = 'required|string|max:100';
            $rules['billingCountry'] = 'required|string|max:2';
        }

        $this->validate($rules);

        $this->refreshTotals($cartService);
        $this->step = 'shipping';
    }

    // ---------------------------------------------------------------
    // Shipping step actions
    // ---------------------------------------------------------------
    public function continueToPayment(CartService $cartService, ShippingService $shippingService): void
    {
        $method = ShippingMethod::from($this->shippingMethod);
        $this->shippingPrice = $shippingService->calculatePrice($method, $this->subTotal);
        $this->totalPrice = $this->subTotal + $this->shippingPrice;

        $this->step = 'payment';
    }

    public function updatedShippingMethod(ShippingService $shippingService): void
    {
        $method = ShippingMethod::from($this->shippingMethod);
        $this->shippingPrice = $shippingService->calculatePrice($method, $this->subTotal);
        $this->totalPrice = $this->subTotal + $this->shippingPrice;
    }

    // ---------------------------------------------------------------
    // Payment step actions
    // ---------------------------------------------------------------
    public function placeOrder(CheckoutService $checkoutService, CartService $cartService): void
    {
        if ($cartService->isEmpty()) {
            $this->redirect(route('cart.index'));

            return;
        }

        $billing = $this->billingIsSameAsShipping
            ? [
                'street' => $this->shippingStreet,
                'city' => $this->shippingCity,
                'zip' => $this->shippingZip,
                'state' => $this->shippingState,
                'country' => $this->shippingCountry,
                'billing_name' => Auth::check() ? Auth::user()->name : $this->guestName,
                'tax_number' => null,
            ]
            : [
                'street' => $this->billingStreet,
                'city' => $this->billingCity,
                'zip' => $this->billingZip,
                'state' => $this->billingState,
                'country' => $this->billingCountry,
                'billing_name' => $this->billingName ?: (Auth::check() ? Auth::user()->name : $this->guestName),
                'tax_number' => $this->billingTaxNumber ?: null,
            ];

        $order = $checkoutService->placeOrder([
            'email' => Auth::check() ? Auth::user()->email : $this->guestEmail,
            'name' => Auth::check() ? Auth::user()->name : $this->guestName,
            'shipping' => [
                'street' => $this->shippingStreet,
                'city' => $this->shippingCity,
                'zip' => $this->shippingZip,
                'state' => $this->shippingState ?: null,
                'country' => $this->shippingCountry,
            ],
            'billing' => $billing,
            'shipping_method' => ShippingMethod::from($this->shippingMethod),
            'payment_method' => PaymentMethod::from($this->paymentMethod),
            'notes' => $this->notes ?: null,
        ]);

        $this->redirect(route('order.confirmation', ['number' => $order->number]));
    }

    // ---------------------------------------------------------------
    // Navigation
    // ---------------------------------------------------------------
    public function back(string $target): void
    {
        $this->step = $target;
    }

    // ---------------------------------------------------------------
    // Computed properties for views
    // ---------------------------------------------------------------

    /** @return Collection<int, Address> */
    #[Computed]
    public function shippingAddresses(): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        return Auth::user()->shippingAddresses()->get();
    }

    /** @return Collection<int, Address> */
    #[Computed]
    public function billingAddresses(): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        return Auth::user()->billingAddresses()->get();
    }

    #[Computed]
    public function isGuest(): bool
    {
        return ! Auth::check();
    }

    // ---------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------
    public function render(CartService $cartService): View
    {
        return view('livewire.shop.checkout.checkout-page', [
            'cartItems' => $cartService->getContent(),
            'shippingAddresses' => $this->shippingAddresses,
            'billingAddresses' => $this->billingAddresses,
            'isGuest' => $this->isGuest,
        ]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    protected function refreshTotals(CartService $cartService): void
    {
        $this->subTotal = $cartService->getSubTotal();
        $this->itemCount = $cartService->getItemCount();
        $this->totalPrice = $this->subTotal + $this->shippingPrice;
    }

    protected function initLoggedInAddresses(): void
    {
        $user = Auth::user();

        $defaultShipping = $user->defaultShippingAddress();
        if ($defaultShipping) {
            $this->selectedShippingAddressId = $defaultShipping->id;
            $this->addingNewShippingAddress = false;
            $this->shippingStreet = $defaultShipping->street ?? '';
            $this->shippingZip = $defaultShipping->zip ?? '';
            $this->shippingCity = $defaultShipping->city ?? '';
            $this->shippingState = $defaultShipping->state ?? '';
            $this->shippingCountry = $defaultShipping->country ?? 'HU';
        } else {
            $this->addingNewShippingAddress = true;
        }

        $defaultBilling = $user->defaultBillingAddress();
        if ($defaultBilling) {
            $this->selectedBillingAddressId = $defaultBilling->id;
            $this->addingNewBillingAddress = false;
        }
    }

    protected function loadShippingAddressFields(int $addressId): void
    {
        $address = Auth::user()->shippingAddresses()
            ->where('addresses.id', $addressId)
            ->first();

        if ($address) {
            $this->shippingStreet = $address->street ?? '';
            $this->shippingZip = $address->zip ?? '';
            $this->shippingCity = $address->city ?? '';
            $this->shippingState = $address->state ?? '';
            $this->shippingCountry = $address->country ?? 'HU';
        }
    }

    protected function loadBillingAddressFields(int $addressId): void
    {
        $address = Auth::user()->billingAddresses()
            ->where('addresses.id', $addressId)
            ->first();

        if ($address) {
            $this->billingStreet = $address->street ?? '';
            $this->billingZip = $address->zip ?? '';
            $this->billingCity = $address->city ?? '';
            $this->billingState = $address->state ?? '';
            $this->billingCountry = $address->country ?? 'HU';
            $this->billingName = $address->pivot->billing_name ?? '';
            $this->billingTaxNumber = $address->pivot->tax_number ?? '';
        }
    }
}
