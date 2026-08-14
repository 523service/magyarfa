<?php

namespace Tests\Feature;

use App\Models\Shop\Product;
use App\Models\Shop\ProductUnitConfig;
use App\Models\Shop\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitCalcApiTest extends TestCase
{
    use RefreshDatabase;

    private Unit $baseUnit;

    private Unit $secondaryUnit;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUnit = Unit::factory()->create([
            'name' => 'm²',
            'slug' => 'm2-api',
            'label' => 'Négyzetméter',
            'label_short' => 'm²',
            'is_base_unit' => true,
        ]);

        $this->secondaryUnit = Unit::factory()->create([
            'name' => 'Bála',
            'slug' => 'bala-api',
            'label' => 'Bála',
            'label_short' => 'bála',
            'is_base_unit' => false,
        ]);

        $this->product = Product::factory()->create(['price' => 260.00]);

        ProductUnitConfig::create([
            'shop_product_id' => $this->product->id,
            'base_unit_id' => $this->baseUnit->id,
            'secondary_unit_id' => $this->secondaryUnit->id,
            'secondary_unit_qty' => 25.0,
            'min_order_qty' => 25.0,
            'order_step' => 25.0,
            'price_per_base_unit' => null,
        ]);
    }

    public function test_returns_404_when_no_unit_config(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}/unit-calc")
            ->assertStatus(404);
    }

    public function test_returns_correct_calculation_for_exact_quantity(): void
    {
        $this->getJson("/api/products/{$this->product->id}/unit-calc?qty=25&unit=base")
            ->assertOk()
            ->assertJson([
                'actual_base_qty' => 25.0,
                'secondary_qty' => 1.0,
                'base_unit_label' => 'm²',
                'secondary_unit_label' => 'bála',
                'price_per_base_unit' => 260.0,
                'total_price' => 6500.0,
                'was_rounded_up' => false,
            ]);
    }

    public function test_rounds_up_to_nearest_secondary_unit(): void
    {
        $response = $this->getJson("/api/products/{$this->product->id}/unit-calc?qty=30&unit=base")
            ->assertOk();

        $this->assertEquals(50.0, $response->json('actual_base_qty'));
        $this->assertEquals(2.0, $response->json('secondary_qty'));
        $this->assertTrue($response->json('was_rounded_up'));
        $this->assertEquals(13000.0, $response->json('total_price'));
    }

    public function test_accepts_secondary_unit_input(): void
    {
        // 2 bála = 50 m²
        $this->getJson("/api/products/{$this->product->id}/unit-calc?qty=2&unit=secondary")
            ->assertOk()
            ->assertJson([
                'actual_base_qty' => 50.0,
                'secondary_qty' => 2.0,
                'was_rounded_up' => false,
            ]);
    }

    public function test_uses_price_per_base_unit_when_set(): void
    {
        $this->product->unitConfig->update(['price_per_base_unit' => 300.00]);

        $this->getJson("/api/products/{$this->product->id}/unit-calc?qty=25&unit=base")
            ->assertOk()
            ->assertJson([
                'price_per_base_unit' => 300.0,
                'total_price' => 7500.0,
            ]);
    }

    public function test_falls_back_to_product_price_when_no_config_price(): void
    {
        $this->getJson("/api/products/{$this->product->id}/unit-calc?qty=25&unit=base")
            ->assertOk()
            ->assertJson(['price_per_base_unit' => 260.0]);
    }
}
