<?php

namespace Tests\Feature;

use App\Models\Shop\Product;
use App\Models\Shop\ProductUnitConfig;
use App\Models\Shop\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitConfigTest extends TestCase
{
    use RefreshDatabase;

    private Unit $baseUnit;

    private Unit $secondaryUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUnit = Unit::factory()->create([
            'name' => 'm²',
            'slug' => 'm2-test',
            'label' => 'Négyzetméter',
            'label_short' => 'm²',
            'is_base_unit' => true,
        ]);

        $this->secondaryUnit = Unit::factory()->create([
            'name' => 'Bála',
            'slug' => 'bala-test',
            'label' => 'Bála',
            'label_short' => 'bála',
            'is_base_unit' => false,
        ]);
    }

    private function makeConfig(array $overrides = []): ProductUnitConfig
    {
        $product = Product::factory()->create();

        return ProductUnitConfig::create(array_merge([
            'shop_product_id' => $product->id,
            'base_unit_id' => $this->baseUnit->id,
            'secondary_unit_id' => $this->secondaryUnit->id,
            'secondary_unit_qty' => 25.0,
            'min_order_qty' => 25.0,
            'order_step' => 25.0,
            'price_per_base_unit' => null,
            'notes' => null,
        ], $overrides));
    }

    // --- roundUpToStep ---

    public function test_round_up_to_step_with_secondary_unit_exact(): void
    {
        $config = $this->makeConfig(['secondary_unit_qty' => 25.0]);

        $this->assertEquals(25.0, $config->roundUpToStep(25.0));
        $this->assertEquals(50.0, $config->roundUpToStep(50.0));
    }

    public function test_round_up_to_step_with_secondary_unit_rounds_up(): void
    {
        $config = $this->makeConfig(['secondary_unit_qty' => 25.0]);

        // 30 m² → 2 bála = 50 m²
        $this->assertEquals(50.0, $config->roundUpToStep(30.0));
        // 1 m² → 1 bála = 25 m²
        $this->assertEquals(25.0, $config->roundUpToStep(1.0));
        // 26 m² → 2 bála = 50 m²
        $this->assertEquals(50.0, $config->roundUpToStep(26.0));
    }

    public function test_round_up_to_step_without_secondary_unit(): void
    {
        $config = $this->makeConfig([
            'secondary_unit_id' => null,
            'secondary_unit_qty' => null,
            'order_step' => 5.0,
        ]);

        $this->assertEquals(5.0, $config->roundUpToStep(3.0));
        $this->assertEquals(10.0, $config->roundUpToStep(10.0));
        $this->assertEquals(15.0, $config->roundUpToStep(11.0));
    }

    // --- toSecondaryUnit ---

    public function test_to_secondary_unit_calculates_correctly(): void
    {
        $config = $this->makeConfig(['secondary_unit_qty' => 25.0]);

        $this->assertEquals(1.0, $config->toSecondaryUnit(25.0));
        $this->assertEquals(2.0, $config->toSecondaryUnit(50.0));
        // Felfelé kerekítés: 26 m² → 2 bála
        $this->assertEquals(2.0, $config->toSecondaryUnit(26.0));
    }

    public function test_to_secondary_unit_returns_null_without_secondary(): void
    {
        $config = $this->makeConfig([
            'secondary_unit_id' => null,
            'secondary_unit_qty' => null,
        ]);

        $this->assertNull($config->toSecondaryUnit(25.0));
    }

    // --- Relations ---

    public function test_product_unit_config_relations(): void
    {
        $config = $this->makeConfig();
        $config->load(['product', 'baseUnit', 'secondaryUnit']);

        $this->assertInstanceOf(Product::class, $config->product);
        $this->assertInstanceOf(Unit::class, $config->baseUnit);
        $this->assertInstanceOf(Unit::class, $config->secondaryUnit);
        $this->assertEquals($this->baseUnit->id, $config->baseUnit->id);
        $this->assertEquals($this->secondaryUnit->id, $config->secondaryUnit->id);
    }

    public function test_product_has_unit_config_relation(): void
    {
        $product = Product::factory()->create();
        ProductUnitConfig::create([
            'shop_product_id' => $product->id,
            'base_unit_id' => $this->baseUnit->id,
            'min_order_qty' => 1.0,
            'order_step' => 1.0,
        ]);

        $this->assertInstanceOf(ProductUnitConfig::class, $product->unitConfig);
    }
}
