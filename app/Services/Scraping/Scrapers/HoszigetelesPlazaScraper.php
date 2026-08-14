<?php

namespace App\Services\Scraping\Scrapers;

use App\Services\Scraping\CompetitorScraperInterface;
use App\Services\Scraping\ScrapedData;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class HoszigetelesPlazaScraper implements CompetitorScraperInterface
{
    private const BASE_URL = 'https://www.hoszigetelesplaza.hu';

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        return str_contains($host, 'hoszigetelesplaza.hu');
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
        );
    }

    private function extractPrice(Crawler $crawler): ?float
    {
        // Primary: <span class="price-value" content="630"> — most reliable, no unit/formatting issues
        try {
            $node = $crawler->filter('span.price-value')->first();
            if ($node->count() > 0) {
                $content = $node->attr('content');
                if ($content !== null && is_numeric($content)) {
                    return (float) $content;
                }
                // Fallback to text if content attr missing
                $parsed = $this->parsePrice($node->text());
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        } catch (Exception) {
        }

        // Secondary: inline JS — var product_information = { ..., price: '630', ... }
        $jsPrice = $this->extractPriceFromJs($crawler);
        if ($jsPrice !== null) {
            return $jsPrice;
        }

        // Tertiary: JSON-LD structured data
        return $this->extractPriceFromJsonLd($crawler);
    }

    private function extractPriceFromJs(Crawler $crawler): ?float
    {
        // The site embeds: var product_information = { ..., price: '630', ... }
        // Key is unquoted: price: '630'  (not 'price': '630')
        try {
            $scripts = $crawler->filter('script');
            foreach ($scripts as $script) {
                // Skip external scripts
                if ($script->hasAttribute('src')) {
                    continue;
                }

                if (preg_match('/product_information\s*=\s*\{[^}]*\bprice["\']?\s*:\s*["\']?([\d.]+)["\']?/s', $script->textContent, $m)) {
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
        // On sale: tr.product-price-cut contains a crossed-out original price,
        // and tr.total .price-value shows the discounted price.
        // We detect a sale by checking if a "price-cut" row exists and extracting the
        // original price from it (the current price is already in extractPrice()).
        try {
            $cutNode = $crawler->filter('tr.product-price-cut .price-value-cut, .price-novat-cut')->first();
            if ($cutNode->count() > 0) {
                $content = $cutNode->attr('content');
                if ($content !== null && is_numeric($content)) {
                    return (float) $content;
                }

                return $this->parsePrice($cutNode->text());
            }
        } catch (Exception) {
        }

        return null;
    }

    private function extractImageUrl(Crawler $crawler): ?string
    {
        // Primary: the main product detail image (id="detail_src_magnifying_small")
        try {
            $node = $crawler->filter('img#detail_src_magnifying_small')->first();
            if ($node->count() > 0) {
                $src = $node->attr('src') ?? '';
                if ($src !== '') {
                    return $this->absoluteUrl($src);
                }
            }
        } catch (Exception) {
        }

        // Secondary: any image inside .image div (the product gallery wrapper)
        try {
            $node = $crawler->filter('div.image img')->first();
            if ($node->count() > 0) {
                $src = $node->attr('src') ?? '';
                if ($src !== '' && ! str_contains($src, 'empty.gif')) {
                    return $this->absoluteUrl($src);
                }
            }
        } catch (Exception) {
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
        // Full description: div#description div.spc — contains <h3> header + <p> paragraphs
        // cleanHtml() strips the <h3> entirely (with its "Részletes leírás" text)
        // and preserves ul/li/br while converting <p> to plain line-separated text.
        try {
            $node = $crawler->filter('#description .spc')->first();
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
        if (! preg_match('/^\s*([\d][\d\s.]*)/u', $raw, $matches)) {
            return null;
        }

        $cleaned = preg_replace('/\s+/', '', $matches[1]);

        if (str_contains($cleaned, '.') && ! str_contains($cleaned, ',')) {
            $cleaned = str_replace('.', '', $cleaned);
        }

        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) && $cleaned !== '' ? (float) $cleaned : null;
    }
}
