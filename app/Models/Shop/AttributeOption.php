<?php

namespace App\Models\Shop;

use Database\Factories\Shop\AttributeOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeOption extends Model
{
    /** @use HasFactory<AttributeOptionFactory> */
    use HasFactory;

    protected $table = 'shop_attribute_options';

    protected $fillable = [
        'shop_attribute_id',
        'value',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<Attribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'shop_attribute_id');
    }

    /** @return BelongsToMany<ProductAttributeValue, $this> */
    public function productValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'shop_product_attribute_value_options',
            'shop_attribute_option_id',
            'shop_product_attribute_value_id'
        );
    }
}
