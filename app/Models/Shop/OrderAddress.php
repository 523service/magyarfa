<?php

namespace App\Models\Shop;

use Database\Factories\Shop\OrderAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderAddress extends Model
{
    /** @use HasFactory<OrderAddressFactory> */
    use HasFactory;

    protected $table = 'shop_order_addresses';

    /** @var list<string> */
    protected $fillable = [
        'country',
        'street',
        'city',
        'state',
        'zip',
        'type',
        'name',
        'billing_name',
        'tax_number',
    ];

    /** @return MorphTo<Model, $this> */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
