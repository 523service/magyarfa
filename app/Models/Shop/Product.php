<?php

namespace App\Models\Shop;

use App\Models\Comment;
use App\Services\PriceResolverService;
use App\Services\Pricing\ProductPriceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;

    protected $table = 'shop_products';

    protected $casts = [
        'featured' => 'boolean',
        'is_homepage' => 'boolean',
        'is_on_sale' => 'boolean',
        'is_visible' => 'boolean',
        'backorder' => 'boolean',
        'requires_shipping' => 'boolean',
        'published_at' => 'date',
        'thickness_cm' => 'decimal:2',
        'calculated_price' => 'decimal:2',
        'price_calculated_at' => 'datetime',
        'ai_description_generated_at' => 'datetime',
        'ai_description_reviewed_at' => 'datetime',
    ];

    /** @return HasMany<CompetitorLink, $this> */
    public function competitorLinks(): HasMany
    {
        return $this->hasMany(CompetitorLink::class);
    }

    /** @param Builder<self> $query */
    public function scopeHomepage(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where('is_homepage', true)
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->latest('published_at');
    }

    /** @param Builder<self> $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where('featured', true)
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->latest('published_at');
    }

    /** @param Builder<self> $query */
    public function scopeOnSale(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where('is_on_sale', true)
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->latest('published_at');
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'shop_brand_id');
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_category_product', 'shop_product_id', 'shop_category_id')->withTimestamps();
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** @return BelongsToMany<Unit, $this> */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'shop_product_unit', 'shop_product_id', 'shop_unit_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** @return HasMany<ProductAttributeValue, $this> */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'shop_product_id');
    }

    /** @return HasOne<ProductUnitConfig, $this> */
    public function unitConfig(): HasOne
    {
        return $this->hasOne(ProductUnitConfig::class, 'shop_product_id');
    }

    /** @return BelongsTo<MaterialBasePrice, $this> */
    public function materialBasePrice(): BelongsTo
    {
        return $this->belongsTo(MaterialBasePrice::class);
    }

    /** @return HasMany<ProductPriceComponent, $this> */
    public function priceComponents(): HasMany
    {
        return $this->hasMany(ProductPriceComponent::class, 'shop_product_id')
            ->orderBy('sort_order');
    }

    /** @return BelongsTo<MaterialBasePrice, $this> */
    public function materialPrice(): BelongsTo
    {
        return $this->belongsTo(MaterialBasePrice::class, 'material_price_id');
    }

    /** @return BelongsTo<SystemTemplate, $this> */
    public function systemTemplate(): BelongsTo
    {
        return $this->belongsTo(SystemTemplate::class, 'system_template_id');
    }

    /**
     * Get the numeric value of a product attribute by its slug.
     * Handles number (number_value), select (first option value parsed as float),
     * and text (text_value parsed as float) attribute types.
     */
    public function getAttributeNumericValue(string $attributeSlug): float
    {
        // Use already-loaded relation when available (avoids N+1)
        if ($this->relationLoaded('attributeValues')) {
            $attrValue = $this->attributeValues->first(function ($av) use ($attributeSlug) {
                return $av->relationLoaded('attribute') && $av->attribute->slug === $attributeSlug;
            });
        }

        if (! isset($attrValue) || $attrValue === null) {
            $attrValue = $this->attributeValues()
                ->whereHas('attribute', fn ($q) => $q->where('slug', $attributeSlug))
                ->with(['attribute', 'options'])
                ->first();
        }

        if (! $attrValue) {
            return 0.0;
        }

        if ($attrValue->number_value !== null) {
            return (float) $attrValue->number_value;
        }

        if ($attrValue->relationLoaded('options') ? $attrValue->options->isNotEmpty() : $attrValue->options()->exists()) {
            $options = $attrValue->relationLoaded('options') ? $attrValue->options : $attrValue->options()->get();

            return (float) $options->first()?->value ?? 0.0;
        }

        if ($attrValue->text_value !== null) {
            return (float) $attrValue->text_value;
        }

        return 0.0;
    }

    /**
     * Get the display unit for the product with fallback logic:
     * 1. Product's primary unit
     * 2. First category's primary unit
     * 3. Default "db"
     *
     * @return Attribute<string, never>
     */
    protected function displayUnit(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // 1. Check for product's primary unit
                $primaryUnit = $this->units()->wherePivot('is_primary', true)->first();
                if ($primaryUnit) {
                    return $primaryUnit->name;
                }

                // 2. Check first category's primary unit
                $firstCategory = $this->categories()->first();
                if ($firstCategory) {
                    $categoryUnit = $firstCategory->units()->wherePivot('is_primary', true)->first();
                    if ($categoryUnit) {
                        return $categoryUnit->name;
                    }
                }

                // 3. Default fallback
                return 'db';
            }
        );
    }

    /**
     * Resolve the effective price using the active pricing system.
     * Uses ProductPriceCalculator when pricing_mode is set, otherwise falls back to legacy resolver.
     */
    public function getResolvedPrice(): float
    {
        if ($this->pricing_mode && $this->pricing_mode !== 'manual') {
            return (float) ($this->calculated_price ?? app(ProductPriceCalculator::class)->calculate($this));
        }

        if ($this->pricing_mode === 'manual') {
            return (float) $this->price;
        }

        return app(PriceResolverService::class)->resolve($this);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('product-images')
            ->useDisk('product-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/webp', 'image/png', 'image/gif'])
            ->registerMediaConversions(function (Media $media): void {
                $this
                    ->addMediaConversion('thumb')
                    ->width(600)
                    ->height(600)
                    ->format('webp')
                    ->quality(85)
                    ->withResponsiveImages()
                    ->nonQueued();
            });
    }

    public function sharedMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'shared_media_id');
    }

    // Single accessor for everywhere in your blade/code
    public function getMainImageUrl(string $conversion = 'thumb'): string
    {
        if ($this->shared_media_id) {
            return $this->sharedMedia?->getUrl($conversion) ?? '';
        }

        return $this->getFirstMediaUrl('product-images', $conversion);
    }

    /**
     * Returns the first Media object for this product, respecting shared_media_id.
     */
    public function getMainMedia(): ?Media
    {
        if ($this->shared_media_id) {
            return $this->sharedMedia;
        }

        return $this->getFirstMedia('product-images');
    }

    //
    public function getMainMediaCollection(): Collection
    {
        if ($this->shared_media_id) {
            // return the single shared media item as a collection
            return Media::where('id', $this->shared_media_id)
                ->get();
        }

        return $this->getMedia('product-images');
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['brand', 'categories', 'media', 'sharedMedia']);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_visible === true;
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'objectID' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => strip_tags((string) $this->description),
            'price' => (float) $this->price,
            'price_formatted' => number_format((float) $this->price, 0, ',', ' ') . ' Ft',
            'featured' => $this->featured,
            'brand_id' => $this->shop_brand_id,
            'brand_name' => $this->brand?->name,
            'category_ids' => $this->categories->pluck('id')->toArray(),
            'category_names' => $this->categories->pluck('name')->toArray(),
            'image_url' => $this->getMainImageUrl('thumb'),
            'url' => route('product.show', $this->slug),
        ];
    }
}
