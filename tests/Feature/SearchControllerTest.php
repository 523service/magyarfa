<?php

namespace Tests\Feature;

use App\Models\Shop\Brand;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── /kereses (search page) ──────────────────────────────────────

    public function test_search_page_returns_200(): void
    {
        $response = $this->withoutVite()->get('/kereses');

        $response->assertStatus(200);
    }

    public function test_search_page_passes_query_to_view(): void
    {
        $response = $this->withoutVite()->get('/kereses?q=test');

        $response->assertStatus(200);
        $response->assertViewHas('query', 'test');
    }

    public function test_search_page_passes_empty_query_by_default(): void
    {
        $response = $this->withoutVite()->get('/kereses');

        $response->assertViewHas('query', '');
    }

    // ── /kereses/defaults ───────────────────────────────────────────

    public function test_defaults_returns_json_structure(): void
    {
        $brand = Brand::factory()->create();

        Product::factory()->count(3)->create([
            'is_visible' => true,
            'shop_brand_id' => $brand->id,
        ]);

        Product::factory()->count(2)->create([
            'is_visible' => true,
            'featured' => true,
            'shop_brand_id' => $brand->id,
        ]);

        $response = $this->getJson('/kereses/defaults');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'latest' => [['title', 'url', 'meta']],
                'popular' => [['title', 'url', 'meta']],
            ]);
    }

    public function test_defaults_excludes_invisible_products(): void
    {
        Product::factory()->count(3)->create(['is_visible' => false]);

        $response = $this->getJson('/kereses/defaults');

        $response->assertStatus(200);
        $response->assertJson(['latest' => [], 'popular' => []]);
    }

    // ── /kereses/autocomplete ───────────────────────────────────────

    public function test_autocomplete_returns_empty_for_short_query(): void
    {
        $response = $this->getJson('/kereses/autocomplete?q=a');

        $response->assertStatus(200)
            ->assertJson(['products' => [], 'categories' => []]);
    }

    public function test_autocomplete_returns_empty_without_query(): void
    {
        $response = $this->getJson('/kereses/autocomplete');

        $response->assertStatus(200)
            ->assertJson(['products' => [], 'categories' => []]);
    }

    public function test_autocomplete_returns_correct_structure(): void
    {
        // Scout uses the 'null' driver in tests (SCOUT_DRIVER is not algolia in test env).
        // We just verify the response shape is correct.
        $response = $this->getJson('/kereses/autocomplete?q=szigetel');

        $response->assertStatus(200)
            ->assertJsonStructure(['products', 'categories']);
    }
}
