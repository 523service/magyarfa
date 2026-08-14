<?php

namespace App\Models\Shop;

use Database\Factories\Shop\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Searchable;

    /**
     * @var string
     */
    protected $table = 'shop_categories';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'meta' => 'array',
    ];

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shop_category_product', 'shop_category_id', 'shop_product_id');
    }

    /** @return BelongsToMany<Unit, $this> */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'shop_category_unit', 'shop_category_id', 'shop_unit_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['products' => fn ($q) => $q->with(['media'])->where('is_visible', true)->limit(1)]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_visible === true;
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        $imageUrl = $this->getFirstMediaUrl()
            ?: $this->products->first()?->getMainImageUrl('thumb');

        return [
            'objectID' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => strip_tags((string) $this->description),
            'image_url' => $imageUrl ?: null,
            'url' => route('category.show', $this->slug),
        ];
    }
}
