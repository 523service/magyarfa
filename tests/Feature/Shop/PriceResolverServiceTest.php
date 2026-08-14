<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\Attribute;
use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\ProductAttributeValue;
use App\Models\Shop\ProductPriceComponent;
use App\Services\PriceResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PriceResolverService;
    }

    // ---------------------------------------------------------------
    // Manual price (price > 0)
    // ---------------------------------------------------------------

    public function test_manual_price_is_returned_when_price_greater_than_zero(): void
    {
        $product = Product::factory()->create(['price' => 2500.00]);

        $this->assertEquals(2500.0, $this->resolver->resolve($product));
    }

    public function test_manual_price_wins_over_base_price_when_set(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 300.0,
            'attribute_slug' => 'vastagsag',
        ]);

        $product = Product::factory()->create([
            'price' => 1000.0,
            'material_base_price_id' => $basePrice->id,
        ]);

        $this->assertEquals(1000.0, $this->resolver->resolve($product));
    }

    // ---------------------------------------------------------------
    // Simple product: materialBasePrice × attribute
    // ---------------------------------------------------------------

    public function test_resolves_price_from_base_price_and_number_attribute(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 282.0,
            'attribute_slug' => 'vastagsag',
            'unit_label' => 'cm',
        ]);

        $attribute = Attribute::factory()->create(['slug' => 'vastagsag', 'type' => 'number']);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $basePrice->id,
        ]);

        ProductAttributeValue::factory()->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'number_value' => 10.0,
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        $this->assertEquals(2820.0, $this->resolver->resolve($product));
    }

    public function test_resolves_price_from_base_price_and_text_attribute(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 100.0,
            'attribute_slug' => 'vastagság-cm',
        ]);

        $attribute = Attribute::factory()->create(['slug' => 'vastagság-cm', 'type' => 'text']);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $basePrice->id,
        ]);

        ProductAttributeValue::factory()->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'text_value' => '15',
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        $this->assertEquals(1500.0, $this->resolver->resolve($product));
    }

    public function test_returns_zero_when_attribute_value_is_zero(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 282.0,
            'attribute_slug' => 'vastagsag',
        ]);

        $attribute = Attribute::factory()->create(['slug' => 'vastagsag', 'type' => 'number']);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $basePrice->id,
        ]);

        ProductAttributeValue::factory()->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'number_value' => 0,
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        $this->assertEquals(0.0, $this->resolver->resolve($product));
    }

    public function test_returns_zero_when_no_attribute_found_for_base_price(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 282.0,
            'attribute_slug' => 'non_existent_slug',
        ]);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $basePrice->id,
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        $this->assertEquals(0.0, $this->resolver->resolve($product));
    }

    // ---------------------------------------------------------------
    // System product: sum of price components (fixed quantity)
    // ---------------------------------------------------------------

    public function test_resolves_price_from_components_with_fixed_quantities(): void
    {
        $eps = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);
        $adhesive = MaterialBasePrice::factory()->create(['price_per_unit' => 150.0]);

        $product = Product::factory()->create(['price' => 0]);

        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $eps->id,
            'quantity' => 10.0,
            'attribute_slug' => null,
            'label' => 'EPS lemez',
            'sort_order' => 1,
        ]);

        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $adhesive->id,
            'quantity' => 1.0,
            'attribute_slug' => null,
            'label' => 'Ragasztó',
            'sort_order' => 2,
        ]);

        $product->load(['priceComponents.materialBasePrice', 'materialBasePrice']);

        // 282 × 10 + 150 × 1 = 2820 + 150 = 2970
        $this->assertEquals(2970.0, $this->resolver->resolve($product));
    }

    // ---------------------------------------------------------------
    // System product: component quantity from product attribute
    // ---------------------------------------------------------------

    public function test_resolves_eps_system_price_with_attribute_slug_on_component(): void
    {
        $eps = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0, 'unit_label' => 'cm']);
        $adhesive = MaterialBasePrice::factory()->create(['price_per_unit' => 450.0, 'unit_label' => 'm2']);
        $mesh = MaterialBasePrice::factory()->create(['price_per_unit' => 380.0, 'unit_label' => 'm2']);

        $attribute = Attribute::factory()->create(['slug' => 'vastagsag', 'type' => 'number']);
        $product = Product::factory()->create(['price' => 0]);

        ProductAttributeValue::factory()->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'number_value' => 10.0,
        ]);

        // EPS: quantity from attribute (vastagsag = 10)
        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $eps->id,
            'quantity' => null,
            'attribute_slug' => 'vastagsag',
            'label' => 'EPS 80 lap',
            'sort_order' => 1,
        ]);

        // Fixed-price components: quantity = 1
        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $adhesive->id,
            'quantity' => 1.0,
            'attribute_slug' => null,
            'label' => 'TDR40 ragasztótapasz',
            'sort_order' => 2,
        ]);

        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $mesh->id,
            'quantity' => 1.0,
            'attribute_slug' => null,
            'label' => 'Üvegszövetháló',
            'sort_order' => 3,
        ]);

        $product->load(['priceComponents.materialBasePrice', 'materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        // (282 × 10) + (450 × 1) + (380 × 1) = 2820 + 450 + 380 = 3650
        $this->assertEquals(3650.0, $this->resolver->resolve($product));
    }

    public function test_same_template_different_thickness_gives_different_price(): void
    {
        $eps = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0, 'unit_label' => 'cm']);
        $adhesive = MaterialBasePrice::factory()->create(['price_per_unit' => 450.0, 'unit_label' => 'm2']);
        $attribute = Attribute::factory()->create(['slug' => 'vastagsag', 'type' => 'number']);

        $product10 = Product::factory()->create(['price' => 0]);
        $product15 = Product::factory()->create(['price' => 0]);

        foreach ([[$product10, 10], [$product15, 15]] as [$product, $thickness]) {
            ProductAttributeValue::factory()->create([
                'shop_product_id' => $product->id,
                'shop_attribute_id' => $attribute->id,
                'number_value' => $thickness,
            ]);

            ProductPriceComponent::create([
                'shop_product_id' => $product->id,
                'material_base_price_id' => $eps->id,
                'quantity' => null,
                'attribute_slug' => 'vastagsag',
                'label' => 'EPS',
                'sort_order' => 1,
            ]);

            ProductPriceComponent::create([
                'shop_product_id' => $product->id,
                'material_base_price_id' => $adhesive->id,
                'quantity' => 1.0,
                'attribute_slug' => null,
                'label' => 'Ragasztó',
                'sort_order' => 2,
            ]);
        }

        $product10->load(['priceComponents.materialBasePrice', 'materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);
        $product15->load(['priceComponents.materialBasePrice', 'materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        // 10cm: 282×10 + 450 = 3270 | 15cm: 282×15 + 450 = 4680
        $this->assertEquals(3270.0, $this->resolver->resolve($product10));
        $this->assertEquals(4680.0, $this->resolver->resolve($product15));
    }

    public function test_components_win_over_material_base_price(): void
    {
        $simpleBasePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 300.0,
            'attribute_slug' => 'vastagsag',
        ]);
        $componentMbp = MaterialBasePrice::factory()->create(['price_per_unit' => 500.0]);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $simpleBasePrice->id,
        ]);

        ProductPriceComponent::create([
            'shop_product_id' => $product->id,
            'material_base_price_id' => $componentMbp->id,
            'quantity' => 2.0,
            'attribute_slug' => null,
            'label' => 'Komponens',
            'sort_order' => 1,
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        // Components win: 500 × 2 = 1000 (not 300 × attribute)
        $this->assertEquals(1000.0, $this->resolver->resolve($product));
    }

    // ---------------------------------------------------------------
    // Fallback
    // ---------------------------------------------------------------

    public function test_returns_zero_when_no_price_information(): void
    {
        $product = Product::factory()->create(['price' => 0]);
        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice']);

        $this->assertEquals(0.0, $this->resolver->resolve($product));
    }

    // ---------------------------------------------------------------
    // Format helper
    // ---------------------------------------------------------------

    public function test_format_returns_hungarian_currency_string(): void
    {
        $product = Product::factory()->create(['price' => 2500.0]);

        $this->assertEquals('2 500 Ft', $this->resolver->format($product));
    }

    public function test_format_uses_resolved_price_not_raw_price(): void
    {
        $basePrice = MaterialBasePrice::factory()->create([
            'price_per_unit' => 282.0,
            'attribute_slug' => 'vastagsag',
        ]);

        $attribute = Attribute::factory()->create(['slug' => 'vastagsag', 'type' => 'number']);

        $product = Product::factory()->create([
            'price' => 0,
            'material_base_price_id' => $basePrice->id,
        ]);

        ProductAttributeValue::factory()->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'number_value' => 10.0,
        ]);

        $product->load(['materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options']);

        $this->assertEquals('2 820 Ft', $this->resolver->format($product));
    }
}
