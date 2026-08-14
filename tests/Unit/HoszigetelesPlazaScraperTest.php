<?php

namespace Tests\Unit;

use App\Services\Scraping\ScrapedData;
use App\Services\Scraping\Scrapers\HoszigetelesPlazaScraper;
use ReflectionClass;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class HoszigetelesPlazaScraperTest extends TestCase
{
    private HoszigetelesPlazaScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new HoszigetelesPlazaScraper;
    }

    public function test_supports_hoszigetelesplaza_domain(): void
    {
        $this->assertTrue($this->scraper->supports('https://www.hoszigetelesplaza.hu/termek/valami'));
        $this->assertTrue($this->scraper->supports('https://hoszigetelesplaza.hu/termek/valami'));
        $this->assertFalse($this->scraper->supports('https://hoszigetelorendszer.com/termek/valami'));
        $this->assertFalse($this->scraper->supports('https://example.com'));
    }

    public function test_parses_price_from_content_attribute(): void
    {
        // Primary source: <span class="price-value" content="630"> — plain number in attribute
        $html = <<<'HTML'
            <html><body>
                <tr class="total">
                    <td class="price-desc left">Ár (bruttó) (27 %):</td>
                    <td colspan="3" class="prices">
                        <div align="left">
                            <span class="price-vat" content="HUF">
                                <span class="price-value def_color" content="630">
                                    630&nbsp;Ft
                                </span>
                            </span>
                        </div>
                    </td>
                </tr>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(630.0, $result->price, 0.01);
        $this->assertNull($result->salePrice);
    }

    public function test_falls_back_to_js_product_information_price(): void
    {
        // No .price-value — fallback to product_information JS variable
        $html = <<<'HTML'
            <html><body>
                <script type="text/javascript">
                    var product_information = {
                        id: '10',
                        name: 'EPS 80',
                        price: '630',
                        category: 'EPS 80',
                        is_variant: false
                    };
                </script>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(630.0, $result->price, 0.01);
    }

    public function test_falls_back_to_json_ld_price(): void
    {
        $html = <<<'HTML'
            <html><body>
                <script type="application/ld+json">
                {
                    "@context": "http://schema.org",
                    "@type": "Product",
                    "name": "EPS 80",
                    "offers": {
                        "@type": "Offer",
                        "price": "630",
                        "priceCurrency": "HUF"
                    }
                }
                </script>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(630.0, $result->price, 0.01);
    }

    public function test_parses_image_from_detail_src_id(): void
    {
        // Primary: img#detail_src_magnifying_small with relative src
        $html = <<<'HTML'
            <html><body>
                <span class="img" id="magnify_src">
                    <img id="detail_src_magnifying_small"
                         src="/fotky2685/fotos/eps-80-homlokzati-hoszigetelo-lemez.jpg"
                         width="200" alt="EPS 80">
                </span>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEquals(
            'https://www.hoszigetelesplaza.hu/fotky2685/fotos/eps-80-homlokzati-hoszigetelo-lemez.jpg',
            $result->imageUrl
        );
    }

    public function test_falls_back_to_og_image(): void
    {
        $html = <<<'HTML'
            <html>
                <head>
                    <meta property="og:image" content="https://www.hoszigetelesplaza.hu/fotky2685/fotos/eps-80-homlokzati-hoszigetelo-lemez.jpg" />
                </head>
                <body></body>
            </html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertEquals(
            'https://www.hoszigetelesplaza.hu/fotky2685/fotos/eps-80-homlokzati-hoszigetelo-lemez.jpg',
            $result->imageUrl
        );
    }

    public function test_parses_description_strips_heading_preserves_text(): void
    {
        // h3 (with "Részletes leírás") must be removed entirely; <p> content kept as text
        $html = <<<'HTML'
            <html><body>
                <div class="part selected" id="description">
                    <div class="spc">
                        <h3>Részletes leírás</h3>
                        <p style="text-align: justify;">Az EPS 80 építési célú homlokzati hőszigetelés 2 cm vastag.</p>
                        <p style="text-align: justify;">Hővezetési tényező: λD = 0,039 W/(mK)</p>
                    </div>
                </div>
            </body></html>
        HTML;

        $result = $this->scrapeFromHtml($html);

        $this->assertStringContainsString('EPS 80 építési célú', $result->description);
        $this->assertStringContainsString('Hővezetési tényező', $result->description);
        // h3 content removed entirely
        $this->assertStringNotContainsString('Részletes leírás', $result->description);
        // p tags stripped (no raw HTML tags)
        $this->assertStringNotContainsString('<p>', $result->description);
        $this->assertStringNotContainsString('<h3>', $result->description);
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

    public function test_parses_full_product_page_sample(): void
    {
        // Full HTML from actual site sample — validates all fields at once
        $samplePath = base_path('.vibe/sample/hoszigetelesplaza_hu_product_sample.html');

        if (! file_exists($samplePath)) {
            $this->markTestSkipped('Sample HTML file not found: ' . $samplePath);
        }

        $html = file_get_contents($samplePath);
        $result = $this->scrapeFromHtml($html);

        $this->assertEqualsWithDelta(630.0, $result->price, 0.01);
        $this->assertNull($result->salePrice);
        $this->assertStringContainsString('hoszigetelesplaza.hu', $result->imageUrl);
        $this->assertStringContainsString('EPS 80', $result->description);
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
        );
    }
}
