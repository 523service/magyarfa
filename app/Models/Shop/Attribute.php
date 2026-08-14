<?php

namespace App\Models\Shop;

use Database\Factories\Shop\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'shop_attributes';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'unit',
        'is_required',
        'is_filterable',
        'is_visible',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return HasMany<AttributeOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'shop_attribute_id')->orderBy('sort_order');
    }

    /** @return HasMany<ProductAttributeValue, $this> */
    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'shop_attribute_id');
    }
}
