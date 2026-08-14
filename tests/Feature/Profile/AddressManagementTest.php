<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\AddressManager;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_manager_component_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->assertStatus(200)
            ->assertSee('Címeim')
            ->assertSee('Szállítási címek')
            ->assertSee('Számlázási címek');
    }

    public function test_user_can_add_shipping_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('street', 'Kossuth utca 10.')
            ->set('zip', '1234')
            ->set('city', 'Budapest')
            ->set('country', 'HU')
            ->set('label', 'Otthon')
            ->set('isDefault', true)
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('addresses', [
            'street' => 'Kossuth utca 10.',
            'zip' => '1234',
            'city' => 'Budapest',
            'country' => 'HU',
        ]);

        $this->assertCount(1, $user->fresh()->shippingAddresses);

        $address = $user->fresh()->shippingAddresses->first();
        $this->assertEquals('Otthon', $address->pivot->label);
        $this->assertTrue((bool) $address->pivot->is_default);
    }

    public function test_user_can_add_billing_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('switchTab', 'billing')
            ->assertSet('activeTab', 'billing')
            ->call('openModal')
            ->set('billingName', 'Test Kft.')
            ->set('taxNumber', '12345678-1-23')
            ->set('street', 'Petőfi utca 5.')
            ->set('zip', '5678')
            ->set('city', 'Szeged')
            ->set('country', 'HU')
            ->set('isDefault', true)
            ->call('save');

        $this->assertCount(1, $user->fresh()->billingAddresses);

        $address = $user->fresh()->billingAddresses->first();
        $this->assertEquals('Test Kft.', $address->pivot->billing_name);
        $this->assertEquals('12345678-1-23', $address->pivot->tax_number);
    }

    public function test_user_can_edit_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'street' => 'Original Street',
            'city' => 'Original City',
            'zip' => '1111',
            'country' => 'HU',
        ]);
        $user->addresses()->attach($address->id, [
            'type' => 'shipping',
            'is_default' => false,
            'label' => 'Original Label',
        ]);

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('openModal', $address->id)
            ->assertSet('editingAddressId', $address->id)
            ->assertSet('street', 'Original Street')
            ->set('street', 'Updated Street')
            ->set('label', 'Updated Label')
            ->call('save');

        $address->refresh();
        $this->assertEquals('Updated Street', $address->street);

        $pivotData = $user->fresh()->addresses()->where('addresses.id', $address->id)->first()->pivot;
        $this->assertEquals('Updated Label', $pivotData->label);
    }

    public function test_user_can_delete_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->id, [
            'type' => 'shipping',
            'is_default' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('delete', $address->id);

        $this->assertCount(0, $user->fresh()->shippingAddresses);
    }

    public function test_user_can_set_default_address(): void
    {
        $user = User::factory()->create();

        $address1 = Address::factory()->create();
        $address2 = Address::factory()->create();

        $user->addresses()->attach($address1->id, [
            'type' => 'shipping',
            'is_default' => true,
        ]);
        $user->addresses()->attach($address2->id, [
            'type' => 'shipping',
            'is_default' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('setDefault', $address2->id);

        $user->refresh();
        $this->assertFalse((bool) $user->addresses()->where('addresses.id', $address1->id)->first()->pivot->is_default);
        $this->assertTrue((bool) $user->addresses()->where('addresses.id', $address2->id)->first()->pivot->is_default);
    }

    public function test_switching_tabs_resets_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->set('street', 'Some Street')
            ->call('switchTab', 'billing')
            ->assertSet('street', '')
            ->assertSet('activeTab', 'billing');
    }

    public function test_only_one_default_shipping_address_allowed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('openModal')
            ->set('street', 'First Street')
            ->set('zip', '1111')
            ->set('city', 'First City')
            ->set('country', 'HU')
            ->set('isDefault', true)
            ->call('save');

        Livewire::test(AddressManager::class)
            ->call('openModal')
            ->set('street', 'Second Street')
            ->set('zip', '2222')
            ->set('city', 'Second City')
            ->set('country', 'HU')
            ->set('isDefault', true)
            ->call('save');

        $user->refresh();
        $defaultAddresses = $user->shippingAddresses()->wherePivot('is_default', true)->get();
        $this->assertCount(1, $defaultAddresses);
        $this->assertEquals('Second Street', $defaultAddresses->first()->street);
    }

    public function test_validation_requires_street_zip_city(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('openModal')
            ->set('street', '')
            ->set('zip', '')
            ->set('city', '')
            ->call('save')
            ->assertHasErrors(['street', 'zip', 'city']);
    }

    public function test_billing_address_requires_billing_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(AddressManager::class)
            ->call('switchTab', 'billing')
            ->call('openModal')
            ->set('street', 'Test Street')
            ->set('zip', '1234')
            ->set('city', 'Test City')
            ->set('country', 'HU')
            ->set('billingName', '')
            ->call('save')
            ->assertHasErrors(['billingName']);
    }
}
