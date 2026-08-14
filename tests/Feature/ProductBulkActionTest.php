<?php

namespace Tests\Feature;

use App\Filament\Clusters\Products\Resources\Products\Support\AttributeFields;
use App\Models\Shop\Attribute;
use App\Models\Shop\AttributeOption;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBulkActionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // assignCategories — sync mode
    // -------------------------------------------------------------------------

    public function test_assign_categories_sync_replaces_existing(): void
    {
        $products = Product::factory()->count(2)->create();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $cat3 = Category::factory()->create();

        // Pre-attach cat1 to both products
        $products->each(fn ($p) => $p->categories()->attach($cat1->id));

        // Simulate the sync action
        $data = ['categories' => [$cat2->id, $cat3->id], 'mode' => 'sync'];
        foreach ($products as $product) {
            $product->categories()->sync($data['categories']);
        }

        foreach ($products as $product) {
            $this->assertFalse($product->fresh()->categories->contains($cat1));
            $this->assertTrue($product->fresh()->categories->contains($cat2));
            $this->assertTrue($product->fresh()->categories->contains($cat3));
        }
    }

    public function test_assign_categories_add_keeps_existing(): void
    {
        $products = Product::factory()->count(2)->create();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $products->each(fn ($p) => $p->categories()->attach($cat1->id));

        // Simulate the add action
        $data = ['categories' => [$cat2->id], 'mode' => 'add'];
        foreach ($products as $product) {
            $product->categories()->syncWithoutDetaching($data['categories']);
        }

        foreach ($products as $product) {
            $fresh = $product->fresh()->categories;
            $this->assertTrue($fresh->contains($cat1));
            $this->assertTrue($fresh->contains($cat2));
        }
    }

    // -------------------------------------------------------------------------
    // assignUnits — sync mode
    // -------------------------------------------------------------------------

    public function test_assign_units_sync_sets_primary_correctly(): void
    {
        $products = Product::factory()->count(2)->create();
        $unit1 = Unit::factory()->create();
        $unit2 = Unit::factory()->create();

        // Simulate the action: unit1 is primary, unit2 is not
        $pivotData = [
            $unit1->id => ['is_primary' => true],
            $unit2->id => ['is_primary' => false],
        ];

        foreach ($products as $product) {
            $product->units()->sync($pivotData);
        }

        foreach ($products as $product) {
            $primaryUnit = $product->units()->wherePivot('is_primary', true)->first();
            $this->assertNotNull($primaryUnit);
            $this->assertTrue($primaryUnit->is($unit1));
        }
    }

    public function test_assign_units_add_does_not_detach_existing(): void
    {
        $products = Product::factory()->count(2)->create();
        $existing = Unit::factory()->create();
        $newUnit = Unit::factory()->create();

        $products->each(fn ($p) => $p->units()->attach($existing->id, ['is_primary' => true]));

        // Simulate add mode (syncWithoutDetaching each pivot entry)
        $pivotData = [$newUnit->id => ['is_primary' => false]];
        foreach ($products as $product) {
            foreach ($pivotData as $unitId => $pivot) {
                $product->units()->syncWithoutDetaching([$unitId => $pivot]);
            }
        }

        foreach ($products as $product) {
            $this->assertTrue($product->units->contains($existing));
            $this->assertTrue($product->units->contains($newUnit));
        }
    }

    // -------------------------------------------------------------------------
    // AttributeFields::saveAttributeValues
    // -------------------------------------------------------------------------

    public function test_save_attribute_values_text(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->text()->create(['is_visible' => true]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => 'hello world',
        ]);

        $this->assertDatabaseHas('shop_product_attribute_values', [
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'text_value' => 'hello world',
        ]);
    }

    public function test_save_attribute_values_number(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->number()->create(['is_visible' => true]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => '42.5',
        ]);

        $this->assertDatabaseHas('shop_product_attribute_values', [
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'number_value' => 42.5,
        ]);
    }

    public function test_save_attribute_values_boolean(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->boolean()->create(['is_visible' => true]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => true,
        ]);

        $this->assertDatabaseHas('shop_product_attribute_values', [
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
            'boolean_value' => 1,
        ]);
    }

    public function test_save_attribute_values_select_syncs_options(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->select()->create(['is_visible' => true]);
        $option = AttributeOption::factory()->create(['shop_attribute_id' => $attribute->id]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => $option->id,
        ]);

        $attrValue = $product->attributeValues()->where('shop_attribute_id', $attribute->id)->first();
        $this->assertNotNull($attrValue);
        $this->assertTrue($attrValue->options->contains($option));
    }

    public function test_save_attribute_values_multiselect_syncs_multiple_options(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->multiselect()->create(['is_visible' => true]);
        $opt1 = AttributeOption::factory()->create(['shop_attribute_id' => $attribute->id]);
        $opt2 = AttributeOption::factory()->create(['shop_attribute_id' => $attribute->id]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => [$opt1->id, $opt2->id],
        ]);

        $attrValue = $product->attributeValues()->where('shop_attribute_id', $attribute->id)->first();
        $this->assertNotNull($attrValue);
        $this->assertTrue($attrValue->options->contains($opt1));
        $this->assertTrue($attrValue->options->contains($opt2));
    }

    public function test_save_attribute_values_empty_deletes_existing(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->text()->create(['is_visible' => true]);

        // Create existing value
        $product->attributeValues()->create([
            'shop_attribute_id' => $attribute->id,
            'text_value' => 'to be deleted',
        ]);

        AttributeFields::saveAttributeValues($product, [
            "attribute_{$attribute->id}" => null,
        ]);

        $this->assertDatabaseMissing('shop_product_attribute_values', [
            'shop_product_id' => $product->id,
            'shop_attribute_id' => $attribute->id,
        ]);
    }

    public function test_save_attribute_values_skips_non_attribute_keys(): void
    {
        $product = Product::factory()->create();

        // No exception should be thrown for non-attribute keys
        AttributeFields::saveAttributeValues($product, [
            'name' => 'Test Product',
            'price' => '1000',
        ]);

        $this->assertDatabaseCount('shop_product_attribute_values', 0);
    }

    public function test_save_attribute_values_applies_to_multiple_products(): void
    {
        $products = Product::factory()->count(3)->create();
        $attribute = Attribute::factory()->text()->create(['is_visible' => true]);
        $data = ["attribute_{$attribute->id}" => 'bulk value'];

        foreach ($products as $product) {
            AttributeFields::saveAttributeValues($product, $data);
        }

        foreach ($products as $product) {
            $this->assertDatabaseHas('shop_product_attribute_values', [
                'shop_product_id' => $product->id,
                'shop_attribute_id' => $attribute->id,
                'text_value' => 'bulk value',
            ]);
        }
    }
}
