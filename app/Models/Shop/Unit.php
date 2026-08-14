<?php

namespace App\Models\Shop;

use Database\Factories\Shop\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'shop_units';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'label',
        'label_short',
        'is_base_unit',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_base_unit' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shop_product_unit', 'shop_unit_id', 'shop_product_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_category_unit', 'shop_unit_id', 'shop_category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** @return HasMany<ProductUnitConfig, $this> */
    public function unitConfigs(): HasMany
    {
        return $this->hasMany(ProductUnitConfig::class, 'base_unit_id');
    }
}
