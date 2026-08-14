<?php

namespace Tests\Feature;

use App\Jobs\ScrapeCompetitorLinkJob;
use App\Models\Shop\CompetitorLink;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapeCompetitorLinkJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeProductHtml(string $price, ?string $salePrice = null, string $description = 'Teszt hőszigetelő leírás', string $shortDescription = 'Vastagság: 2 cm'): string
    {
        $salePriceHtml = $salePrice
            ? "<div class=\"sale\">{$salePrice} Ft</div>"
            : '';

        return <<<HTML
            <html>
                <head><meta property="og:image" content="https://hoszigetelorendszer.com/module-files/webshopproduct/image/abc.png"></head>
                <body>
                    <script>
                        var price = {$price};
                        function calcprice() { \$('.product-prices .regular').text(price + ' Ft'); }
                    </script>
                    <div class="row g-4">
                        <div class="col-md-8">
                            <form action="javascript:void(0);">
                                <div class="product-prices mb-4">
                                    <div class="regular">{$price} Ft / m<sup>2</sup></div>
                                    {$salePriceHtml}
                                </div>
                                <div class="content-text block-text mb-4">
                                    <ul><li>{$shortDescription}</li></ul>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="product-image">
                        <a class="media-wrapper lb" href="/module-files/webshopproduct/image/abc.png">
                            <img src="/module-files/webshopproduct/mid/abc.png" class="img-fluid" alt="Termék">
                        </a>
                    </div>
                    <div class="tab-pane fade show active" id="product-body">
                        <div class="content-text"><p>{$description}</p></div>
                    </div>
                </body>
            </html>
        HTML;
    }

    public function test_job_updates_competitor_link_on_success(): void
    {
        Http::fake([
            'hoszigetelorendszer.com/*' => Http::response($this->makeProductHtml('606'), 200),
        ]);

        $product = Product::factory()->create(['is_visible' => false]);
        $link = CompetitorLink::create([
            'product_id' => $product->id,
            'url' => 'https://hoszigetelorendszer.com/hoszigeteles/eps-80/2cm',
            'competitor_name' => 'hoszigetelorendszer.com',
        ]);

        ScrapeCompetitorLinkJob::dispatchSync($link);

        $link->refresh();
        $this->assertEquals('success', $link->scrape_status);
        $this->assertEqualsWithDelta(606.0, (float) $link->scraped_price, 0.01);
        $this->assertEquals('Teszt hőszigetelő leírás', $link->scraped_description);
        $this->assertStringContainsString('Vastagság: 2 cm', $link->scraped_short_description);
        $this->assertEquals('https://hoszigetelorendszer.com/module-files/webshopproduct/mid/abc.png', $link->scraped_image_url);
        $this->assertNotNull($link->last_scraped_at);
        $this->assertNull($link->scrape_error);
    }

    public function test_job_sets_failed_status_on_http_error(): void
    {
        Http::fake([
            'hoszigetelorendszer.com/*' => Http::response('Not Found', 404),
        ]);

        $product = Product::factory()->create(['is_visible' => false]);
        $link = CompetitorLink::create([
            'product_id' => $product->id,
            'url' => 'https://hoszigetelorendszer.com/termek/nem-letezik',
            'competitor_name' => 'hoszigetelorendszer.com',
        ]);

        ScrapeCompetitorLinkJob::dispatchSync($link);

        $link->refresh();
        $this->assertEquals('failed', $link->scrape_status);
        $this->assertNotNull($link->scrape_error);
    }

    public function test_job_sets_failed_status_for_unknown_domain(): void
    {
        $product = Product::factory()->create(['is_visible' => false]);
        $link = CompetitorLink::create([
            'product_id' => $product->id,
            'url' => 'https://ismeretlen-oldal.hu/termek/valami',
            'competitor_name' => 'ismeretlen-oldal.hu',
        ]);

        ScrapeCompetitorLinkJob::dispatchSync($link);

        $link->refresh();
        $this->assertEquals('failed', $link->scrape_status);
        $this->assertStringContainsString('ismeretlen-oldal.hu', $link->scrape_error);
    }

    public function test_competitor_name_is_auto_derived_from_url(): void
    {
        $product = Product::factory()->create(['is_visible' => false]);
        $link = CompetitorLink::create([
            'product_id' => $product->id,
            'url' => 'https://www.hoszigetelorendszer.com/termek/valami',
        ]);

        $this->assertEquals('hoszigetelorendszer.com', $link->competitor_name);
    }

    public function test_job_updates_sale_price_when_present(): void
    {
        Http::fake([
            'hoszigetelorendszer.com/*' => Http::response($this->makeProductHtml('606', '490'), 200),
        ]);

        $product = Product::factory()->create(['is_visible' => false]);
        $link = CompetitorLink::create([
            'product_id' => $product->id,
            'url' => 'https://hoszigetelorendszer.com/termek/akcios',
            'competitor_name' => 'hoszigetelorendszer.com',
        ]);

        ScrapeCompetitorLinkJob::dispatchSync($link);

        $link->refresh();
        $this->assertEquals('success', $link->scrape_status);
        $this->assertEqualsWithDelta(606.0, (float) $link->scraped_price, 0.01);
        $this->assertEqualsWithDelta(490.0, (float) $link->scraped_sale_price, 0.01);
    }
}
