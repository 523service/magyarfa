<?php

namespace Tests\Feature\Shop;

use App\Actions\Shop\CloneProductAction;
use App\Models\Shop\Attribute;
use App\Models\Shop\AttributeOption;
use App\Models\Shop\Category;
use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\ProductAttributeValue;
use App\Models\Shop\ProductUnitConfig;
use App\Models\Shop\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloneProductActionTest extends TestCase
{
    use RefreshDatabase;

    private CloneProductAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CloneProductAction;
    }

    public function test_clone_creates_new_product(): void
    {
        $product = Product::factory()->create();

        $clone = $this->action->handle($product);

        $this->assertDatabaseCount('shop_products', 2);
        $this->assertNotEquals($product->id, $clone->id);
    }

    public function test_clone_appends_masolat_to_name(): void
    {
        $product = Product::factory()->create(['name' => 'Teszt termék', 'slug' => 'teszt-termek']);

        $clone = $this->action->handle($product);

        $this->assertEquals('Teszt termék (Másolat)', $clone->name);
    }

    public function test_clone_generates_unique_slug(): void
    {
        $product = Product::factory()->create(['name' => 'Teszt', 'slug' => 'teszt']);

        $clone = $this->action->handle($product);

        $this->assertEquals('teszt-masolat', $clone->slug);
        $this->assertDatabaseHas('shop_products', ['slug' => 'teszt-masolat']);
    }

    public function test_clone_generates_incremented_slug_when_first_taken(): void
    {
        $product = Product::factory()->create(['name' => 'Teszt', 'slug' => 'teszt']);
        Product::factory()->create(['name' => 'Teszt másolat', 'slug' => 'teszt-masolat']);

        $clone = $this->action->handle($product);

        $this->assertEquals('teszt-masolat-2', $clone->slug);
    }

    public function test_clone_nulls_out_sku_and_barcode(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-001', 'barcode' => 'BAR-001']);

        $clone = $this->action->handle($product);

        $this->assertNull($clone->sku);
        $this->assertNull($clone->barcode);
    }

    public function test_clone_sets_is_visible_false(): void
    {
        $product = Product::factory()->create(['is_visible' => true]);

        $clone = $this->action->handle($product);

        $this->assertFalse($clone->is_visible);
    }

    public function test_clone_preserves_other_attributes(): void
    {
        $product = Product::factory()->create([
            'price' => 9900.00,
            'is_homepage' => true,
            'featured' => true,
        ]);

        $clone = $this->action->handle($product);

        $this->assertEquals(9900.00, (float) $clone->price);
        $this->assertTrue($clone->is_homepage);
        $this->assertTrue($clone->featured);
    }

    public function test_clone_preserves_shared_media_id(): void
    {
        // shared_media_id is null by default; verify replicate() preserves it as-is
        $product = Product::factory()->create(['shared_media_id' => null]);

        $clone = $this->action->handle($product);

        $this->assertNull($clone->shared_media_id);
    }

    public function test_clone_syncs_categories(): void
    {
        $product = Product::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $product->categories()->sync($categories->pluck('id'));

        $clone = $this->action->handle($product);

        $this->assertCount(2, $clone->categories);
        $this->assertEquals(
            $product->categories->pluck('id')->sort()->values(),
            $clone->fresh()->categories->pluck('id')->sort()->values()
        );
    }

    public function test_clone_syncs_units_with_pivot(): void
    {
        $product = Product::factory()->create();
        $primaryUnit = Unit::factory()->create();
        $secondaryUnit = Unit::factory()->create();

        $product->units()->sync([
            $primaryUnit->id => ['is_primary' => true],
            $secondaryUnit->id => ['is_primary' => false],
        ]);

        $clone = $this->action->handle($product);

        $cloneUnits = $clone->fresh()->units()->withPivot('is_primary')->get();
        $this->assertCount(2, $cloneUnits);

        $clonePrimary = $cloneUnits->firstWhere('id', $primaryUnit->id);
        $cloneSecondary = $cloneUnits->firstWhere('id', $secondaryUnit->id);

        $this->assertTrue((bool) $clonePrimary->pivot->is_primary);
        $this->assertFalse((bool) $cloneSecondary->pivot->is_primary);
    }

    public function test_clone_replicates_unit_config(): void
    {
        $product = Product::factory()->create();
        $baseUnit = Unit::factory()->create();

        ProductUnitConfig::create([
            'shop_product_id' => $product->id,
            'base_unit_id' => $baseUnit->id,
            'min_order_qty' => 2.0,
            'order_step' => 0.5,
        ]);

        $clone = $this->action->handle($product);

        $cloneConfig = $clone->fresh()->unitConfig;
        $this->assertNotNull($cloneConfig);
        $this->assertEquals($baseUnit->id, $cloneConfig->base_unit_id);
        $this->assertEquals('2.0000', $cloneConfig->min_order_qty);
        $this->assertEquals('0.5000', $cloneConfig->order_step);
        $this->assertNotEquals($product->unitConfig->id, $cloneConfig->id);
    }

    public function test_clone_replicates_attribute_values(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->text()->create();

        ProductAttributeValue::factory()->withText('test value')->create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
        ]);

        $clone = $this->action->handle($product);

        $cloneValues = $clone->fresh()->attributeValues;
        $this->assertCount(1, $cloneValues);
        $this->assertEquals('test value', $cloneValues->first()->text_value);
        $this->assertEquals($attribute->id, $cloneValues->first()->shop_attribute_id);
    }

    public function test_clone_replicates_attribute_values_with_options(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->select()->create();
        $option = AttributeOption::factory()->create(['shop_attribute_id' => $attribute->id]);

        $attrValue = ProductAttributeValue::create([
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
        ]);
        $attrValue->options()->sync([$option->id]);

        $clone = $this->action->handle($product);

        $cloneValue = $clone->fresh()->attributeValues()->with('options')->first();
        $this->assertNotNull($cloneValue);
        $this->assertCount(1, $cloneValue->options);
        $this->assertEquals($option->id, $cloneValue->options->first()->id);
        $this->assertNotEquals($attrValue->id, $cloneValue->id);
    }

    public function test_clone_replicates_price_components(): void
    {
        $product = Product::factory()->create();
        $material = MaterialBasePrice::factory()->create();

        $product->priceComponents()->create([
            'material_base_price_id' => $material->id,
            'quantity' => 1.5,
            'label' => 'Alap anyag',
            'sort_order' => 1,
        ]);

        $clone = $this->action->handle($product);

        $cloneComponents = $clone->fresh()->priceComponents;
        $this->assertCount(1, $cloneComponents);
        $this->assertEquals($material->id, $cloneComponents->first()->material_base_price_id);
        $this->assertEquals('1.5000', $cloneComponents->first()->quantity);
        $this->assertEquals('Alap anyag', $cloneComponents->first()->label);
    }

    public function test_clone_does_not_copy_comments(): void
    {
        $product = Product::factory()->create();

        $clone = $this->action->handle($product);

        $this->assertCount(0, $clone->fresh()->comments);
    }

    public function test_clone_skips_unit_config_when_original_has_none(): void
    {
        $product = Product::factory()->create();

        $clone = $this->action->handle($product);

        $this->assertNull($clone->fresh()->unitConfig);
    }
}
