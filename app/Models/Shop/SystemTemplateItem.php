<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemTemplateItem extends Model
{
    use HasFactory;

    protected $table = 'system_template_items';

    protected $fillable = [
        'system_template_id',
        'material_price_id',
        'label',
        'quantity_type',
        'quantity_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity_value' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<SystemTemplate, $this> */
    public function systemTemplate(): BelongsTo
    {
        return $this->belongsTo(SystemTemplate::class);
    }

    /** @return BelongsTo<MaterialBasePrice, $this> */
    public function materialPrice(): BelongsTo
    {
        return $this->belongsTo(MaterialBasePrice::class, 'material_price_id');
    }
}
