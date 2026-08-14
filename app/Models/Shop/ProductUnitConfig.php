<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnitConfig extends Model
{
    protected $table = 'shop_product_unit_configs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shop_product_id',
        'base_unit_id',
        'secondary_unit_id',
        'secondary_unit_qty',
        'min_order_qty',
        'order_step',
        'price_per_base_unit',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secondary_unit_qty' => 'decimal:4',
            'min_order_qty' => 'decimal:4',
            'order_step' => 'decimal:4',
            'price_per_base_unit' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'shop_product_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function secondaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'secondary_unit_id');
    }

    /**
     * Hány másodlagos egység (pl. bála) az adott alap mennyiség?
     * Mindig felfelé kerekít.
     */
    public function toSecondaryUnit(float $baseQty): ?float
    {
        if (! $this->secondary_unit_qty) {
            return null;
        }

        return ceil($baseQty / $this->secondary_unit_qty);
    }

    /**
     * Felfelé kerekített tényleges rendelési mennyiség (alap egységben).
     * Ha van másodlagos egység, egész bálára kerekít.
     * Ha nincs, lépésköz szerint kerekít.
     */
    public function roundUpToStep(float $requestedQty): float
    {
        if ($this->secondary_unit_qty) {
            $units = ceil($requestedQty / (float) $this->secondary_unit_qty);

            return $units * (float) $this->secondary_unit_qty;
        }

        return ceil($requestedQty / (float) $this->order_step) * (float) $this->order_step;
    }
}
