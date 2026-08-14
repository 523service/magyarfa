<?php

namespace App\Services\Scraping;

readonly class ScrapedData
{
    public function __construct(
        public ?float $price = null,
        public ?float $salePrice = null,
        public ?string $imageUrl = null,
        public ?string $description = null,
        public ?string $shortDescription = null,
    ) {}
}
