<?php

namespace Tests\Feature\Pricing;

use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\SystemTemplate;
use App\Models\Shop\SystemTemplateItem;
use App\Services\Pricing\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ProductPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProductPriceCalculator;
    }

    // -------------------------------------------------------------------
    // Manual mode
    // -------------------------------------------------------------------

    public function test_manual_mode_returns_price_field(): void
    {
        $product = Product::factory()->create([
            'pricing_mode' => 'manual',
            'price' => 3500.00,
        ]);

        $this->assertEquals(3500.0, $this->calculator->calculate($product));
    }

    public function test_manual_mode_explain_output(): void
    {
        $product = Product::factory()->create([
            'pricing_mode' => 'manual',
            'price' => 1200.00,
        ]);

        $result = $this->calculator->explain($product);

        $this->assertEquals('manual', $result['pricing_mode']);
        $this->assertEquals(1200.0, $result['final_price']);
    }

    // -------------------------------------------------------------------
    // Formula mode — board_by_thickness_cm
    // -------------------------------------------------------------------

    public function test_formula_board_by_thickness_calculates_correctly(): void
    {
        $materialPrice = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);

        $product = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 10,
        ]);

        // 282 * 10 = 2820
        $this->assertEquals(2820.0, $this->calculator->calculate($product));
    }

    public function test_formula_board_by_thickness_15cm(): void
    {
        $materialPrice = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);

        $product = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 15,
        ]);

        // 282 * 15 = 4230
        $this->assertEquals(4230.0, $this->calculator->calculate($product));
    }

    public function test_formula_returns_zero_when_no_material_price(): void
    {
        $product = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => null,
            'thickness_cm' => 10,
        ]);

        $this->assertEquals(0.0, $this->calculator->calculate($product));
    }

    public function test_formula_explain_output_structure(): void
    {
        $materialPrice = MaterialBasePrice::factory()->create([
            'name' => 'EPS 80',
            'price_per_unit' => 282.0,
        ]);

        $product = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 10,
        ]);

        $result = $this->calculator->explain($product);

        $this->assertEquals('formula', $result['pricing_mode']);
        $this->assertEquals('board_by_thickness_cm', $result['formula_type']);
        $this->assertEquals('EPS 80', $result['material']);
        $this->assertEquals(282.0, $result['unit_price']);
        $this->assertEquals(10.0, $result['thickness_cm']);
        $this->assertEquals(2820.0, $result['final_price']);
    }

    // -------------------------------------------------------------------
    // System template mode
    // -------------------------------------------------------------------

    public function test_system_template_calculates_multi_line_total(): void
    {
        $eps = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);
        $adhesive = MaterialBasePrice::factory()->create(['price_per_unit' => 150.0]);
        $mesh = MaterialBasePrice::factory()->create(['price_per_unit' => 180.0]);
        $primer = MaterialBasePrice::factory()->create(['price_per_unit' => 200.0]);

        $template = SystemTemplate::factory()->create(['name' => 'EPS Standard']);

        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $eps->id,
            'label' => 'EPS lap',
            'quantity_type' => 'product_thickness_cm',
            'quantity_value' => null,
            'sort_order' => 1,
        ]);
        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $adhesive->id,
            'label' => 'Ragasztó',
            'quantity_type' => 'fixed',
            'quantity_value' => 3.5,
            'sort_order' => 2,
        ]);
        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $mesh->id,
            'label' => 'Háló',
            'quantity_type' => 'fixed',
            'quantity_value' => 4.5,
            'sort_order' => 3,
        ]);
        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $primer->id,
            'label' => 'Alapozó',
            'quantity_type' => 'fixed',
            'quantity_value' => 2.0,
            'sort_order' => 4,
        ]);

        $product = Product::factory()->create([
            'pricing_mode' => 'system_template',
            'system_template_id' => $template->id,
            'thickness_cm' => 10,
        ]);

        // 282*10 + 150*3.5 + 180*4.5 + 200*2.0 = 2820 + 525 + 810 + 400 = 4555
        $this->assertEquals(4555.0, $this->calculator->calculate($product));
    }

    public function test_same_template_different_thickness_gives_different_price(): void
    {
        $eps = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);
        $adhesive = MaterialBasePrice::factory()->create(['price_per_unit' => 450.0]);

        $template = SystemTemplate::factory()->create();

        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $eps->id,
            'quantity_type' => 'product_thickness_cm',
            'quantity_value' => null,
            'sort_order' => 1,
        ]);
        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $adhesive->id,
            'quantity_type' => 'fixed',
            'quantity_value' => 1.0,
            'sort_order' => 2,
        ]);

        $product10 = Product::factory()->create([
            'pricing_mode' => 'system_template',
            'system_template_id' => $template->id,
            'thickness_cm' => 10,
        ]);
        $product15 = Product::factory()->create([
            'pricing_mode' => 'system_template',
            'system_template_id' => $template->id,
            'thickness_cm' => 15,
        ]);

        // 10cm: 282*10 + 450*1 = 3270
        // 15cm: 282*15 + 450*1 = 4680
        $this->assertEquals(3270.0, $this->calculator->calculate($product10));
        $this->assertEquals(4680.0, $this->calculator->calculate($product15));
    }

    public function test_system_template_returns_zero_when_no_template(): void
    {
        $product = Product::factory()->create([
            'pricing_mode' => 'system_template',
            'system_template_id' => null,
            'thickness_cm' => 10,
        ]);

        $this->assertEquals(0.0, $this->calculator->calculate($product));
    }

    public function test_system_template_explain_output_structure(): void
    {
        $eps = MaterialBasePrice::factory()->create(['name' => 'EPS 80', 'price_per_unit' => 282.0]);
        $adhesive = MaterialBasePrice::factory()->create(['name' => 'Ragasztó', 'price_per_unit' => 150.0]);

        $template = SystemTemplate::factory()->create(['name' => 'EPS Standard System']);

        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $eps->id,
            'label' => 'EPS board',
            'quantity_type' => 'product_thickness_cm',
            'quantity_value' => null,
            'sort_order' => 1,
        ]);
        SystemTemplateItem::factory()->create([
            'system_template_id' => $template->id,
            'material_price_id' => $adhesive->id,
            'label' => 'Adhesive',
            'quantity_type' => 'fixed',
            'quantity_value' => 3.5,
            'sort_order' => 2,
        ]);

        $product = Product::factory()->create([
            'pricing_mode' => 'system_template',
            'system_template_id' => $template->id,
            'thickness_cm' => 10,
        ]);

        $result = $this->calculator->explain($product);

        $this->assertEquals('system_template', $result['pricing_mode']);
        $this->assertEquals('EPS Standard System', $result['template']);
        $this->assertCount(2, $result['lines']);
        $this->assertEquals(282.0, $result['lines'][0]['unit_price']);
        $this->assertEquals(10.0, $result['lines'][0]['quantity']);
        $this->assertEquals('product_thickness_cm', $result['lines'][0]['source']);
        $this->assertEquals(150.0, $result['lines'][1]['unit_price']);
        $this->assertEquals(3.5, $result['lines'][1]['quantity']);
        $this->assertEquals('fixed', $result['lines'][1]['source']);
        $this->assertEquals(3345.0, $result['final_price']); // 2820 + 525
    }

    // -------------------------------------------------------------------
    // recalculateProduct
    // -------------------------------------------------------------------

    public function test_recalculate_product_saves_calculated_price(): void
    {
        $materialPrice = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);

        $product = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 10,
            'calculated_price' => null,
        ]);

        $this->calculator->recalculateProduct($product);
        $product->refresh();

        $this->assertEquals(2820.0, (float) $product->calculated_price);
        $this->assertNotNull($product->price_calculated_at);
    }

    // -------------------------------------------------------------------
    // recalculateByMaterialPrice
    // -------------------------------------------------------------------

    public function test_recalculate_by_material_price_updates_affected_products(): void
    {
        $materialPrice = MaterialBasePrice::factory()->create(['price_per_unit' => 282.0]);

        $product1 = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 10,
        ]);
        $product2 = Product::factory()->create([
            'pricing_mode' => 'formula',
            'formula_type' => 'board_by_thickness_cm',
            'material_price_id' => $materialPrice->id,
            'thickness_cm' => 15,
        ]);

        $materialPrice->price_per_unit = 295.0;
        $materialPrice->save();

        $this->calculator->recalculateByMaterialPrice($materialPrice);

        $product1->refresh();
        $product2->refresh();

        $this->assertEquals(2950.0, (float) $product1->calculated_price);
        $this->assertEquals(4425.0, (float) $product2->calculated_price);
    }
}
