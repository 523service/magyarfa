<?php

namespace App\Services\Scraping\Scrapers;

use App\Services\Scraping\CompetitorScraperInterface;
use App\Services\Scraping\ScrapedData;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class HoszigetelorendszerScraper implements CompetitorScraperInterface
{
    private const BASE_URL = 'https://hoszigetelorendszer.com';

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        return str_contains($host, 'hoszigetelorendszer.com');
    }

    public function scrape(string $url): ScrapedData
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language' => 'hu-HU,hu;q=0.9',
        ])
            ->withOptions(['verify' => app()->isProduction()])
            ->timeout(30)
            ->get($url);

        $response->throw();

        $crawler = new Crawler($response->body());

        return new ScrapedData(
            price: $this->extractPrice($crawler),
            salePrice: $this->extractSalePrice($crawler),
            imageUrl: $this->extractImageUrl($crawler),
            description: $this->extractDescription($crawler),
            shortDescription: $this->extractShortDescription($crawler),
        );
    }

    private function extractPrice(Crawler $crawler): ?float
    {
        // Primary: inline JS variable "var price = 606;" — plain number, no unit/formatting issues
        $jsPrice = $this->extractPriceFromJs($crawler);
        if ($jsPrice !== null) {
            return $jsPrice;
        }

        // Secondary: CSS selector .product-prices .regular (may include units like "Ft / m²")
        $selectors = [
            '.product-prices .regular',
            '.product-prices .price',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    $parsed = $this->parsePrice($node->text());
                    if ($parsed !== null) {
                        return $parsed;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        // Fallback: JSON-LD structured data
        return $this->extractPriceFromJsonLd($crawler);
    }

    private function extractPriceFromJs(Crawler $crawler): ?float
    {
        // The site embeds: var price = 606;
        try {
            $scripts = $crawler->filter('script:not([src])');
            foreach ($scripts as $script) {
                if (preg_match('/\bvar\s+price\s*=\s*([\d.]+)\s*;/', $script->textContent, $m)) {
                    $value = (float) $m[1];
                    if ($value > 0) {
                        return $value;
                    }
                }
            }
        } catch (Exception) {
        }

        return null;
    }

    private function extractSalePrice(Crawler $crawler): ?float
    {
        // When on sale, the discounted price is in .sale or .special, original in .regular
        $selectors = [
            '.product-prices .sale',
            '.product-prices .special-price',
            '.product-prices .discounted',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    return $this->parsePrice($node->text());
                }
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }

    private function extractImageUrl(Crawler $crawler): ?string
    {
        // .product-image wraps the main product image (a.media-wrapper > img)
        $selectors = [
            '.product-image .media-wrapper img',
            '.product-image img',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    $src = $node->attr('src') ?? '';
                    if ($src === '') {
                        continue;
                    }

                    return $this->absoluteUrl($src);
                }
            } catch (Exception) {
                continue;
            }
        }

        // Fallback: og:image meta tag
        try {
            $og = $crawler->filter('meta[property="og:image"]')->first();
            if ($og->count() > 0) {
                return $og->attr('content');
            }
        } catch (Exception) {
        }

        return null;
    }

    private function extractDescription(Crawler $crawler): ?string
    {
        // Full description tab: #product-body .content-text
        // Short specs block: .content-text.block-text (appears above the tabs)
        $selectors = [
            '#product-body .content-text',
            '.content-text.block-text',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    $clean = $this->cleanHtml($node->html());
                    if ($clean !== null) {
                        return $clean;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }

    private function extractShortDescription(Crawler $crawler): ?string
    {
        // Short specs block inside the product form: .row form .content-text.block-text
        // This contains key bullet points (vastagság, anyag, λ, méret etc.)
        try {
            $node = $crawler->filter('.row form .content-text')->first();
            if ($node->count() > 0) {
                return $this->cleanHtml($node->html());
            }
        } catch (Exception) {
        }

        return null;
    }

    /**
     * Strips HTML to a readable subset: keeps <ul>, <ol>, <li>, <br> with no attributes;
     * removes h1-h6 with their content entirely; strips all other tags but preserves their text.
     */
    private function cleanHtml(string $html): ?string
    {
        // Remove headings together with their content
        $html = preg_replace('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', '', $html) ?? $html;

        // Add newlines before closing block tags so paragraphs don't run together
        $html = preg_replace('/<\/(p|div|blockquote)>/i', "\n", $html) ?? $html;

        // Strip every tag except ul, ol, li, br
        $html = strip_tags($html, '<ul><ol><li><br>');

        // Remove all attributes from the allowed tags
        $html = preg_replace('/<(ul|ol|li|br)\s[^>]*>/i', '<$1>', $html) ?? $html;

        // Collapse spaces / tabs on each line
        $html = preg_replace('/[ \t]+/', ' ', $html) ?? $html;

        // Collapse 3+ consecutive newlines to a single blank line
        $html = preg_replace('/\n{3,}/', "\n\n", $html) ?? $html;

        $html = trim($html);

        return $html !== '' ? $html : null;
    }

    private function extractPriceFromJsonLd(Crawler $crawler): ?float
    {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');
            foreach ($scripts as $script) {
                $data = json_decode($script->textContent, true);
                if (isset($data['@type']) && $data['@type'] === 'Product') {
                    $price = $data['offers']['price'] ?? null;
                    if ($price !== null) {
                        return (float) $price;
                    }
                }
            }
        } catch (Exception) {
        }

        return null;
    }

    private function absoluteUrl(string $src): string
    {
        if (str_starts_with($src, 'http')) {
            return $src;
        }

        return self::BASE_URL . '/' . ltrim($src, '/');
    }

    private function parsePrice(string $raw): ?float
    {
        // Extract the leading number only — stops before any letter/unit ("Ft", "/ m²", etc.)
        // Handles: "606 Ft / m²" → 606, "1 234 Ft" → 1234, "12.345 Ft" → 12345
        // The <sup>2</sup> inside "m²" would append "2" to the text, so we must not use
        // a global digit-only strip — instead we anchor to the start of the string.
        if (! preg_match('/^\s*([\d][\d\s.]*)/u', $raw, $matches)) {
            return null;
        }

        $cleaned = preg_replace('/\s+/', '', $matches[1]);

        // European thousands separator: "12.345" → "12345" (no comma present)
        if (str_contains($cleaned, '.') && ! str_contains($cleaned, ',')) {
            $cleaned = str_replace('.', '', $cleaned);
        }

        // Decimal comma: "1234,56" → "1234.56"
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) && $cleaned !== '' ? (float) $cleaned : null;
    }
}
