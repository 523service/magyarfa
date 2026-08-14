<?php

namespace App\Models;

use App\Models\Shop\Brand;
use App\Models\Shop\Customer;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $table = 'addresses';

    /** @var list<string> */
    protected $fillable = [
        'country',
        'street',
        'city',
        'state',
        'zip',
    ];

    /** @return MorphToMany<Customer, $this> */
    public function customers(): MorphToMany
    {
        return $this->morphedByMany(Customer::class, 'addressable');
    }

    /** @return MorphToMany<Brand, $this> */
    public function brands(): MorphToMany
    {
        return $this->morphedByMany(Brand::class, 'addressable');
    }

    /** @return MorphToMany<User, $this> */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'addressable')
            ->withPivot(['type', 'is_default', 'label', 'billing_name', 'tax_number'])
            ->withTimestamps();
    }
}
