<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialBasePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price_per_unit',
        'attribute_slug',
        'unit_label',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<ProductPriceComponent, $this> */
    public function priceComponents(): HasMany
    {
        return $this->hasMany(ProductPriceComponent::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<SystemTemplateItem, $this> */
    public function systemTemplateItems(): HasMany
    {
        return $this->hasMany(SystemTemplateItem::class, 'material_price_id');
    }
}
