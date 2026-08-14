<?php

namespace App\Livewire\Profile;

use App\Models\Address;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AddressManager extends Component
{
    public string $activeTab = 'shipping';

    public bool $showModal = false;

    public ?int $editingAddressId = null;

    #[Validate('nullable|string|max:50')]
    public string $label = '';

    #[Validate('required|string|max:255')]
    public string $street = '';

    #[Validate('required|string|max:20')]
    public string $zip = '';

    #[Validate('required|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('required|string|max:2')]
    public string $country = 'HU';

    #[Validate('required_if:activeTab,billing|nullable|string|max:255')]
    public string $billingName = '';

    #[Validate('nullable|string|max:50')]
    public string $taxNumber = '';

    public bool $isDefault = false;

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForm();
    }

    public function openModal(?int $addressId = null): void
    {
        $this->resetForm();

        if ($addressId) {
            $this->editingAddressId = $addressId;
            $this->loadAddress($addressId);
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function loadAddress(int $addressId): void
    {
        $user = Auth::user();
        $address = $user->addresses()
            ->where('addresses.id', $addressId)
            ->first();

        if ($address) {
            $this->label = $address->pivot->label ?? '';
            $this->street = $address->street ?? '';
            $this->zip = $address->zip ?? '';
            $this->city = $address->city ?? '';
            $this->state = $address->state ?? '';
            $this->country = $address->country ?? 'HU';
            $this->billingName = $address->pivot->billing_name ?? '';
            $this->taxNumber = $address->pivot->tax_number ?? '';
            $this->isDefault = (bool) $address->pivot->is_default;
        }
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        if ($this->editingAddressId) {
            $this->updateAddress($user);
        } else {
            $this->createAddress($user);
        }

        $this->closeModal();
    }

    protected function createAddress($user): void
    {
        $address = Address::create([
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'state' => $this->state ?: null,
            'country' => $this->country,
        ]);

        if ($this->isDefault) {
            $this->clearDefaultForType($user);
        }

        $user->addresses()->attach($address->id, [
            'type' => $this->activeTab,
            'is_default' => $this->isDefault,
            'label' => $this->label ?: null,
            'billing_name' => $this->activeTab === 'billing' ? $this->billingName : null,
            'tax_number' => $this->activeTab === 'billing' ? ($this->taxNumber ?: null) : null,
        ]);
    }

    protected function updateAddress($user): void
    {
        $address = Address::find($this->editingAddressId);

        if (! $address) {
            return;
        }

        $address->update([
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'state' => $this->state ?: null,
            'country' => $this->country,
        ]);

        if ($this->isDefault) {
            $this->clearDefaultForType($user);
        }

        $user->addresses()->updateExistingPivot($address->id, [
            'is_default' => $this->isDefault,
            'label' => $this->label ?: null,
            'billing_name' => $this->activeTab === 'billing' ? $this->billingName : null,
            'tax_number' => $this->activeTab === 'billing' ? ($this->taxNumber ?: null) : null,
        ]);
    }

    protected function clearDefaultForType($user): void
    {
        $addresses = $this->activeTab === 'shipping'
            ? $user->shippingAddresses()->get()
            : $user->billingAddresses()->get();

        foreach ($addresses as $address) {
            if ($address->pivot->is_default && $address->id !== $this->editingAddressId) {
                $user->addresses()->updateExistingPivot($address->id, [
                    'is_default' => false,
                ]);
            }
        }
    }

    public function setDefault(int $addressId): void
    {
        $user = Auth::user();

        $this->clearDefaultForType($user);

        $user->addresses()->updateExistingPivot($addressId, [
            'is_default' => true,
        ]);
    }

    public function delete(int $addressId): void
    {
        $user = Auth::user();
        $user->addresses()->detach($addressId);

        $orphanAddress = Address::find($addressId);
        if ($orphanAddress && $orphanAddress->users()->count() === 0
            && $orphanAddress->customers()->count() === 0
            && $orphanAddress->brands()->count() === 0) {
            $orphanAddress->delete();
        }
    }

    protected function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->label = '';
        $this->street = '';
        $this->zip = '';
        $this->city = '';
        $this->state = '';
        $this->country = 'HU';
        $this->billingName = '';
        $this->taxNumber = '';
        $this->isDefault = false;
        $this->resetValidation();
    }

    /** @return Collection<int, Address> */
    public function getShippingAddressesProperty(): Collection
    {
        return Auth::user()->shippingAddresses()->get();
    }

    /** @return Collection<int, Address> */
    public function getBillingAddressesProperty(): Collection
    {
        return Auth::user()->billingAddresses()->get();
    }

    public function render()
    {
        return view('livewire.profile.address-manager');
    }
}
