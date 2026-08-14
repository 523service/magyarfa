<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'shop_product_attribute_values';

    protected $fillable = [
        'shop_product_id',
        'shop_attribute_id',
        'text_value',
        'number_value',
        'boolean_value',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'number_value' => 'decimal:2',
        'boolean_value' => 'boolean',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'shop_product_id');
    }

    /** @return BelongsTo<Attribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'shop_attribute_id');
    }

    /** @return BelongsToMany<AttributeOption, $this> */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeOption::class,
            'shop_product_attribute_value_options',
            'shop_product_attribute_value_id',
            'shop_attribute_option_id'
        );
    }
}
