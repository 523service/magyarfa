<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceComponent extends Model
{
    protected $fillable = [
        'shop_product_id',
        'material_base_price_id',
        'quantity',
        'attribute_slug',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'shop_product_id');
    }

    /** @return BelongsTo<MaterialBasePrice, $this> */
    public function materialBasePrice(): BelongsTo
    {
        return $this->belongsTo(MaterialBasePrice::class);
    }
}
