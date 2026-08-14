<?php

namespace App\Services\Scraping;

use App\Models\Shop\CompetitorLink;
use Throwable;

class CompetitorLinkScraperService
{
    /** @param iterable<CompetitorScraperInterface> $scrapers */
    public function __construct(private readonly iterable $scrapers) {}

    public function scrape(CompetitorLink $link): void
    {
        $scraper = $this->findScraper($link->url);

        if ($scraper === null) {
            $link->update([
                'scrape_status' => 'failed',
                'scrape_error' => "Nincs scraper ehhez a domainhez: {$link->competitor_name}",
            ]);

            return;
        }

        try {
            $data = $scraper->scrape($link->url);

            $link->update([
                'scraped_price' => $data->price,
                'scraped_sale_price' => $data->salePrice,
                'scraped_image_url' => $data->imageUrl,
                'scraped_description' => $data->description,
                'scraped_short_description' => $data->shortDescription,
                'last_scraped_at' => now(),
                'scrape_status' => 'success',
                'scrape_error' => null,
            ]);
        } catch (Throwable $e) {
            $link->update([
                'scrape_status' => 'failed',
                'scrape_error' => $e->getMessage(),
            ]);
        }
    }

    private function findScraper(string $url): ?CompetitorScraperInterface
    {
        foreach ($this->scrapers as $scraper) {
            if ($scraper->supports($url)) {
                return $scraper;
            }
        }

        return null;
    }
}
