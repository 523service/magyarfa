<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorLink extends Model
{
    protected $table = 'shop_competitor_links';

    protected $fillable = [
        'product_id',
        'url',
        'competitor_name',
        'scraped_price',
        'scraped_sale_price',
        'scraped_image_url',
        'scraped_description',
        'scraped_short_description',
        'scraped_others',
        'meta',
        'last_scraped_at',
        'scrape_status',
        'scrape_error',
    ];

    protected function casts(): array
    {
        return [
            'scraped_price' => 'decimal:2',
            'scraped_sale_price' => 'decimal:2',
            'last_scraped_at' => 'datetime',
            'scraped_others' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            if (empty($link->competitor_name)) {
                $host = parse_url($link->url, PHP_URL_HOST) ?? '';
                $link->competitor_name = ltrim($host, 'www.');
            }
        });
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Returns the favicon URL for this competitor.
     * Looks for a pre-placed webp in public/img/{domain_with_dots_as_underscores}.webp.
     * Falls back to a placehold.co image with 1-2 letter initials derived from the domain.
     */
    public function getCompetitorLogoAttribute(): string
    {
        /** @var array<string, string> $map */
        static $map = [
            'hoszigetelorendszer.com' => 'hoszigetelorendszer_com.webp',
            'hoszigetelesplaza.hu' => 'hoszigetelespaza_hu.webp',
        ];

        $filename = $map[$this->competitor_name] ?? null;

        if ($filename !== null && file_exists(public_path("img/{$filename}"))) {
            return asset("img/{$filename}");
        }

        return 'https://placehold.co/16x16?text=' . urlencode($this->competitorInitials());
    }

    private function competitorInitials(): string
    {
        // Strip TLD, split by non-alpha, take first char of each word — max 2 chars
        $name = preg_replace('/\.[a-z]{2,}$/', '', $this->competitor_name ?? '');
        $words = preg_split('/[^a-z]+/i', $name, -1, PREG_SPLIT_NO_EMPTY);

        return strtoupper(implode('', array_map(
            fn (string $w): string => substr($w, 0, 1),
            array_slice($words ?? [], 0, 2)
        )));
    }
}
