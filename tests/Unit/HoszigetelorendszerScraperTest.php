<?php

namespace Tests\Unit;

use App\Services\Scraping\ScrapedData;
use App\Services\Scraping\Scrapers\HoszigetelorendszerScraper;
use ReflectionClass;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class HoszigetelorendszerScraperTest extends TestCase
{
    private HoszigetelorendszerScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new HoszigetelorendszerScraper;
    }

    public function test_supports_hoszigetelorendszer_domain(): void
    {
        $this->assertTrue($this->scraper->supports('https://hoszigetelorendszer.com/termek/valami'));
        $this->assertTrue($this->scraper->supports('https://www.hoszigetelorendszer.com/termek/valami'));
        $this->assertFalse($this->scraper->supports('https://other-site.hu/termek/valami'));
        $this->assertFalse($this->scraper->supports('https://example.com'));
    }

    public function test_parses_price_from_inline_js_variable(): void
    {
        // Primary source: "var price = 606;" — plain number, stable regardless of unit display
        $html = <<<'HTML'
            <html><body>
                <script>
                    var price = 606;
                    function calcprice() { $('.product-prices .regular').text(price + ' Ft'); }
                </script>
                <div class="product-prices mb-4">
                    <div class="regular">606 Ft / m<sup>2</sup></div>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(606.0, $result->price, 0.01);
        $this->assertNull($result->salePrice);
    }

    public function test_falls_back_to_css_selector_price_when_no_js_variable(): void
    {
        // No JS variable — fallback to .product-prices .regular
        $html = <<<'HTML'
            <html><body>
                <div class="product-prices mb-4">
                    <div class="regular">606 Ft / m<sup>2</sup></div>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(606.0, $result->price, 0.01);
    }

    public function test_falls_back_to_json_ld_price(): void
    {
        // Fallback to JSON-LD structured data when CSS selector fails
        $html = <<<'HTML'
            <html><body>
                <script type="application/ld+json">
                {"@type":"Product","offers":{"@type":"Offer","price":"606","priceCurrency":"HUF"}}
                </script>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(606.0, $result->price, 0.01);
    }

    public function test_parses_product_image_url_as_absolute(): void
    {
        // Actual site uses relative URLs: /module-files/webshopproduct/mid/...
        $html = <<<'HTML'
            <html><body>
                <div class="product-image">
                    <a class="media-wrapper lb" href="/module-files/webshopproduct/image/abc.png">
                        <img src="/module-files/webshopproduct/mid/abc.png" class="img-fluid" alt="Termék">
                    </a>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEquals('https://hoszigetelorendszer.com/module-files/webshopproduct/mid/abc.png', $result->imageUrl);
    }

    public function test_parses_og_image_as_fallback(): void
    {
        $html = <<<'HTML'
            <html>
                <head><meta property="og:image" content="https://hoszigetelorendszer.com/module-files/webshopproduct/image/abc.png"></head>
                <body></body>
            </html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEquals('https://hoszigetelorendszer.com/module-files/webshopproduct/image/abc.png', $result->imageUrl);
    }

    public function test_parses_full_description_from_product_body_tab(): void
    {
        // Actual site: #product-body .content-text
        $html = <<<'HTML'
            <html><body>
                <div class="tab-pane fade show active" id="product-body">
                    <div class="content-text">
                        <p>Ez egy kiváló hőszigetelő anyag részletes leírása.</p>
                        <ul><li>Vastagság: 2 cm</li></ul>
                    </div>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertStringContainsString('kiváló hőszigetelő', $result->description);
    }

    public function test_parses_short_spec_block_as_description_fallback(): void
    {
        // .content-text.block-text appears above tabs on the actual site
        $html = <<<'HTML'
            <html><body>
                <div class="content-text block-text mb-4">
                    <ul>
                        <li>Vastagság: 2 cm</li>
                        <li>Anyag: expandált polisztirol</li>
                    </ul>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertStringContainsString('Vastagság: 2 cm', $result->description);
    }

    public function test_returns_null_for_missing_fields(): void
    {
        $html = '<html><body><p>Nincs termék info</p></body></html>';

        $result = $this->scrapeFromHtml($html);

        $this->assertNull($result->price);
        $this->assertNull($result->salePrice);
        $this->assertNull($result->imageUrl);
        $this->assertNull($result->description);
    }

    public function test_description_preserves_ul_li_and_strips_heading_and_p(): void
    {
        // ul/li must survive; h2 (with content) and p tags must be stripped
        $html = <<<'HTML'
            <html><body>
                <div class="content-text block-text mb-4">
                    <h2><strong>Termékcím</strong></h2>
                    <ul>
                        <li>Vastagság: 2 cm</li>
                        <li>Anyag: expandált polisztirol</li>
                    </ul>
                    <p><strong>Szállítási költség:</strong> 735.000 Ft felett díjmentes.</p>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        // h2 content removed entirely
        $this->assertStringNotContainsString('Termékcím', $result->description);
        // ul/li preserved as HTML tags
        $this->assertStringContainsString('<ul>', $result->description);
        $this->assertStringContainsString('<li>Vastagság: 2 cm</li>', $result->description);
        // p tag stripped but text kept
        $this->assertStringContainsString('Szállítási költség', $result->description);
        $this->assertStringNotContainsString('<p>', $result->description);
        $this->assertStringNotContainsString('<strong>', $result->description);
    }

    public function test_parses_short_description_from_form_content_block(): void
    {
        // .row form .content-text — the spec bullet block inside the product form
        $html = <<<'HTML'
            <html><body>
                <div class="row g-4">
                    <div class="col-md-8">
                        <form action="javascript:void(0);">
                            <div class="product-prices mb-4">
                                <div class="regular">606 Ft / m<sup>2</sup></div>
                            </div>
                            <div class="content-text block-text mb-4">
                                <h2><strong>Thermodam EPS 80 homlokzati hőszigetelő lemez 2 cm</strong></h2>
                                <ul>
                                    <li>Vastagság: 2 cm</li>
                                    <li>Anyag: expandált polisztirol</li>
                                    <li>Hővezetési tényező (λ): 0,038 W/mK</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertStringContainsString('Vastagság: 2 cm', $result->shortDescription);
        $this->assertStringContainsString('Hővezetési tényező', $result->shortDescription);
    }

    public function test_short_description_is_null_when_block_absent(): void
    {
        $html = '<html><body><p>Nincs form blokk</p></body></html>';

        $result = $this->scrapeFromHtml($html);

        $this->assertNull($result->shortDescription);
    }

    private function scrapeFromHtml(string $html): ScrapedData
    {
        $crawler = new Crawler($html);
        $ref = new ReflectionClass($this->scraper);

        return new ScrapedData(
            price: $ref->getMethod('extractPrice')->invoke($this->scraper, $crawler),
            salePrice: $ref->getMethod('extractSalePrice')->invoke($this->scraper, $crawler),
            imageUrl: $ref->getMethod('extractImageUrl')->invoke($this->scraper, $crawler),
            description: $ref->getMethod('extractDescription')->invoke($this->scraper, $crawler),
            shortDescription: $ref->getMethod('extractShortDescription')->invoke($this->scraper, $crawler),
        );
    }
}
