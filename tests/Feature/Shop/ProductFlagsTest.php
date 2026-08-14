<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFlagsTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // scopeHomepage
    // ---------------------------------------------------------------

    public function test_homepage_scope_returns_only_homepage_visible_products(): void
    {
        Product::factory()->create(['is_visible' => true, 'is_homepage' => true]);
        Product::factory()->create(['is_visible' => true, 'is_homepage' => false]);
        Product::factory()->create(['is_visible' => false, 'is_homepage' => true]);

        $results = Product::homepage()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is_homepage);
        $this->assertTrue($results->first()->is_visible);
    }

    public function test_homepage_scope_excludes_hidden_products(): void
    {
        Product::factory()->create(['is_visible' => false, 'is_homepage' => true]);

        $this->assertCount(0, Product::homepage()->get());
    }

    // ---------------------------------------------------------------
    // scopeFeatured
    // ---------------------------------------------------------------

    public function test_featured_scope_returns_only_featured_visible_products(): void
    {
        Product::factory()->create(['is_visible' => true, 'featured' => true]);
        Product::factory()->create(['is_visible' => true, 'featured' => false]);
        Product::factory()->create(['is_visible' => false, 'featured' => true]);

        $results = Product::featured()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->featured);
        $this->assertTrue($results->first()->is_visible);
    }

    public function test_featured_scope_excludes_hidden_products(): void
    {
        Product::factory()->create(['is_visible' => false, 'featured' => true]);

        $this->assertCount(0, Product::featured()->get());
    }

    // ---------------------------------------------------------------
    // scopeOnSale
    // ---------------------------------------------------------------

    public function test_on_sale_scope_returns_only_sale_visible_products(): void
    {
        Product::factory()->create(['is_visible' => true, 'is_on_sale' => true]);
        Product::factory()->create(['is_visible' => true, 'is_on_sale' => false]);
        Product::factory()->create(['is_visible' => false, 'is_on_sale' => true]);

        $results = Product::onSale()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is_on_sale);
        $this->assertTrue($results->first()->is_visible);
    }

    public function test_on_sale_scope_excludes_hidden_products(): void
    {
        Product::factory()->create(['is_visible' => false, 'is_on_sale' => true]);

        $this->assertCount(0, Product::onSale()->get());
    }

    // ---------------------------------------------------------------
    // Flags are independent – a product can carry multiple flags
    // ---------------------------------------------------------------

    public function test_product_can_have_all_flags_simultaneously(): void
    {
        Product::factory()->create([
            'is_visible' => true,
            'is_homepage' => true,
            'featured' => true,
            'is_on_sale' => true,
        ]);

        $this->assertCount(1, Product::homepage()->get());
        $this->assertCount(1, Product::featured()->get());
        $this->assertCount(1, Product::onSale()->get());
    }

    public function test_flags_are_independent_of_each_other(): void
    {
        Product::factory()->create(['is_visible' => true, 'is_homepage' => true, 'featured' => false, 'is_on_sale' => false]);
        Product::factory()->create(['is_visible' => true, 'is_homepage' => false, 'featured' => true, 'is_on_sale' => false]);
        Product::factory()->create(['is_visible' => true, 'is_homepage' => false, 'featured' => false, 'is_on_sale' => true]);

        $this->assertCount(1, Product::homepage()->get());
        $this->assertCount(1, Product::featured()->get());
        $this->assertCount(1, Product::onSale()->get());
    }

    // ---------------------------------------------------------------
    // HomeController passes all three collections to the view
    // ---------------------------------------------------------------

    public function test_shop_home_passes_homepage_products_to_view(): void
    {
        Product::factory()->create(['is_visible' => true, 'is_homepage' => true]);
        Product::factory()->create(['is_visible' => true, 'is_homepage' => false]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewHas('homepageProducts');
        $this->assertCount(1, $response->viewData('homepageProducts'));
    }

    public function test_shop_home_passes_featured_products_to_view(): void
    {
        Product::factory()->create(['is_visible' => true, 'featured' => true]);
        Product::factory()->create(['is_visible' => true, 'featured' => false]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewHas('featuredProducts');
        $this->assertCount(1, $response->viewData('featuredProducts'));
    }

    public function test_shop_home_passes_sale_products_to_view(): void
    {
        Product::factory()->create(['is_visible' => true, 'is_on_sale' => true]);
        Product::factory()->create(['is_visible' => true, 'is_on_sale' => false]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewHas('saleProducts');
        $this->assertCount(1, $response->viewData('saleProducts'));
    }

    public function test_shop_home_limits_homepage_products_to_six(): void
    {
        Product::factory()->count(10)->create(['is_visible' => true, 'is_homepage' => true]);

        $response = $this->get(route('home'));

        $this->assertCount(6, $response->viewData('homepageProducts'));
    }

    public function test_shop_home_limits_sale_products_to_eight(): void
    {
        Product::factory()->count(12)->create(['is_visible' => true, 'is_on_sale' => true]);

        $response = $this->get(route('home'));

        $this->assertCount(8, $response->viewData('saleProducts'));
    }
}
