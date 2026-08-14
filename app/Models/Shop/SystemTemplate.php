<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemTemplate extends Model
{
    use HasFactory;

    protected $table = 'system_templates';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<SystemTemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SystemTemplateItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'system_template_id');
    }
}
