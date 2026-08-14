<?php

namespace Tests\Feature;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateSitemapTest extends TestCase
{
    use RefreshDatabase;

    private string $sitemapPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sitemapPath = public_path('sitemap.xml');
        File::delete($this->sitemapPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->sitemapPath);
        parent::tearDown();
    }

    public function test_command_creates_sitemap_file(): void
    {
        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $this->assertFileExists($this->sitemapPath);
    }

    public function test_sitemap_contains_home_url(): void
    {
        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $content = File::get($this->sitemapPath);
        $this->assertStringContainsString(route('home'), $content);
    }

    public function test_sitemap_contains_visible_category_urls(): void
    {
        $visible = Category::factory()->create(['is_visible' => true, 'slug' => 'lathato-kat']);
        Category::factory()->create(['is_visible' => false, 'slug' => 'rejtett-kat']);

        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $content = File::get($this->sitemapPath);
        $this->assertStringContainsString(route('category.show', $visible->slug), $content);
        $this->assertStringNotContainsString(route('category.show', 'rejtett-kat'), $content);
    }

    public function test_sitemap_contains_visible_product_urls(): void
    {
        $visible = Product::factory()->create(['is_visible' => true, 'slug' => 'lathato-termek']);
        Product::factory()->create(['is_visible' => false, 'slug' => 'rejtett-termek']);

        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $content = File::get($this->sitemapPath);
        $this->assertStringContainsString(route('product.show', $visible->slug), $content);
        $this->assertStringNotContainsString(route('product.show', 'rejtett-termek'), $content);
    }

    public function test_sitemap_excludes_search_page(): void
    {
        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $content = File::get($this->sitemapPath);
        $this->assertStringNotContainsString(route('search.index'), $content);
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $xml = simplexml_load_file($this->sitemapPath);
        $this->assertNotFalse($xml, 'sitemap.xml is not valid XML');
    }
}
