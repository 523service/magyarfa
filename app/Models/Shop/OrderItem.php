<?php

namespace App\Models\Shop;

use Database\Factories\Shop\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'shop_order_items';

    /** @var list<string> */
    protected $fillable = [
        'shop_order_id',
        'shop_product_id',
        'qty',
        'unit_name',
        'secondary_qty',
        'secondary_unit',
        'unit_price',
        'sort',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'shop_product_id');
    }
}
