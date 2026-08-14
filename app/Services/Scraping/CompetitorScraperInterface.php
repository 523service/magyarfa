<?php

namespace App\Services\Scraping;

interface CompetitorScraperInterface
{
    public function supports(string $url): bool;

    public function scrape(string $url): ScrapedData;
}
