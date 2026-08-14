# Bulk Attribute Setting for Products

This document provides SQL queries and Tinker commands for setting up example attribute values for multiple products at once.

## Overview

The product attributes system uses an EAV (Entity-Attribute-Value) pattern with the following tables:
- `shop_attributes` - Attribute definitions
- `shop_attribute_options` - Options for select/multiselect types
- `shop_product_attribute_values` - Product attribute values
- `shop_product_attribute_value_options` - Pivot table for select/multiselect values

## Available Attributes (from Seeder)

| ID | Name       | Type        | Unit | Options Count |
|----|------------|-------------|------|---------------|
| 1  | Magasság   | number      | cm   | 0             |
| 2  | Szélesség  | number      | cm   | 0             |
| 3  | Hosszúság  | number      | cm   | 0             |
| 4  | Mélység    | number      | cm   | 0             |
| 5  | Súly       | number      | kg   | 0             |
| 6  | Szín       | select      | -    | 8             |
| 7  | Kiszerelés | select      | -    | 5             |
| 8  | Anyag      | multiselect | -    | 6             |

## Method 1: SQL Query (Direct Database)

### Insert Number-Type Attributes

```sql
-- Set example attributes for the first 10 products
-- This demonstrates all attribute types: number, select, multiselect

-- Insert attribute values for products 1-10
INSERT INTO shop_product_attribute_values
    (shop_product_id, shop_attribute_id, text_value, number_value, boolean_value, created_at, updated_at)
VALUES
    -- Product 1: Dimensions (numbers)
    (1, 1, NULL, 100.00, NULL, NOW(), NOW()), -- Magasság: 100 cm
    (1, 2, NULL, 50.00, NULL, NOW(), NOW()),  -- Szélesség: 50 cm
    (1, 3, NULL, 200.00, NULL, NOW(), NOW()), -- Hosszúság: 200 cm
    (1, 5, NULL, 5.50, NULL, NOW(), NOW()),   -- Súly: 5.5 kg

    -- Product 2: Dimensions
    (2, 1, NULL, 120.00, NULL, NOW(), NOW()),
    (2, 2, NULL, 60.00, NULL, NOW(), NOW()),
    (2, 3, NULL, 240.00, NULL, NOW(), NOW()),
    (2, 5, NULL, 7.20, NULL, NOW(), NOW()),

    -- Product 3-5: Similar pattern
    (3, 1, NULL, 80.00, NULL, NOW(), NOW()),
    (3, 2, NULL, 40.00, NULL, NOW(), NOW()),
    (3, 5, NULL, 3.50, NULL, NOW(), NOW()),

    (4, 1, NULL, 150.00, NULL, NOW(), NOW()),
    (4, 2, NULL, 75.00, NULL, NOW(), NOW()),
    (4, 3, NULL, 300.00, NULL, NOW(), NOW()),

    (5, 1, NULL, 90.00, NULL, NOW(), NOW()),
    (5, 2, NULL, 45.00, NULL, NOW(), NOW()),
    (5, 5, NULL, 4.20, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    number_value = VALUES(number_value),
    updated_at = NOW();
```

### Insert Select Attributes (Szín - Color)

First, create the attribute value entries:

```sql
-- Create attribute value entries for Szín (attribute_id=6)
INSERT INTO shop_product_attribute_values
    (shop_product_id, shop_attribute_id, created_at, updated_at)
VALUES
    (1, 6, NOW(), NOW()),
    (2, 6, NOW(), NOW()),
    (3, 6, NOW(), NOW()),
    (4, 6, NOW(), NOW()),
    (5, 6, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    updated_at = NOW();
```

Then link to options:

```sql
-- Link products to color options
INSERT INTO shop_product_attribute_value_options
    (shop_product_attribute_value_id, shop_attribute_option_id, created_at, updated_at)
SELECT
    pav.id,
    ao.id,
    NOW(),
    NOW()
FROM shop_product_attribute_values pav
CROSS JOIN shop_attribute_options ao
WHERE pav.shop_product_id IN (1, 2, 3, 4, 5)
    AND pav.shop_attribute_id = 6
    AND ao.shop_attribute_id = 6
    AND (
        (pav.shop_product_id = 1 AND ao.value = 'Fehér') OR
        (pav.shop_product_id = 2 AND ao.value = 'Fekete') OR
        (pav.shop_product_id = 3 AND ao.value = 'Kék') OR
        (pav.shop_product_id = 4 AND ao.value = 'Szürke') OR
        (pav.shop_product_id = 5 AND ao.value = 'Fehér')
    )
ON DUPLICATE KEY UPDATE
    updated_at = NOW();
```

### Insert Select Attributes (Kiszerelés - Packaging)

```sql
-- Create attribute value entries for Kiszerelés (attribute_id=7)
INSERT INTO shop_product_attribute_values
    (shop_product_id, shop_attribute_id, created_at, updated_at)
VALUES
    (1, 7, NOW(), NOW()),
    (2, 7, NOW(), NOW()),
    (3, 7, NOW(), NOW()),
    (4, 7, NOW(), NOW()),
    (5, 7, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Link products to packaging options
INSERT INTO shop_product_attribute_value_options
    (shop_product_attribute_value_id, shop_attribute_option_id, created_at, updated_at)
SELECT
    pav.id,
    ao.id,
    NOW(),
    NOW()
FROM shop_product_attribute_values pav
CROSS JOIN shop_attribute_options ao
WHERE pav.shop_product_id IN (1, 2, 3, 4, 5)
    AND pav.shop_attribute_id = 7
    AND ao.shop_attribute_id = 7
    AND (
        (pav.shop_product_id = 1 AND ao.value = 'Darabos') OR
        (pav.shop_product_id = 2 AND ao.value = 'Csomag (10db)') OR
        (pav.shop_product_id = 3 AND ao.value = 'Dobozos') OR
        (pav.shop_product_id = 4 AND ao.value = 'Palettás') OR
        (pav.shop_product_id = 5 AND ao.value = 'Csomag (5db)')
    )
ON DUPLICATE KEY UPDATE
    updated_at = NOW();
```

### Insert Multiselect Attributes (Anyag - Materials)

```sql
-- Create attribute value entries for Anyag (attribute_id=8)
INSERT INTO shop_product_attribute_values
    (shop_product_id, shop_attribute_id, created_at, updated_at)
VALUES
    (1, 8, NOW(), NOW()),
    (2, 8, NOW(), NOW()),
    (3, 8, NOW(), NOW()),
    (4, 8, NOW(), NOW()),
    (5, 8, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Link products to material options (multiple materials per product)
INSERT INTO shop_product_attribute_value_options
    (shop_product_attribute_value_id, shop_attribute_option_id, created_at, updated_at)
SELECT
    pav.id,
    ao.id,
    NOW(),
    NOW()
FROM shop_product_attribute_values pav
CROSS JOIN shop_attribute_options ao
WHERE pav.shop_product_id IN (1, 2, 3, 4, 5)
    AND pav.shop_attribute_id = 8
    AND ao.shop_attribute_id = 8
    AND (
        (pav.shop_product_id = 1 AND ao.value IN ('EPS', 'Grafit')) OR
        (pav.shop_product_id = 2 AND ao.value IN ('XPS', 'Polisztirol')) OR
        (pav.shop_product_id = 3 AND ao.value IN ('Üveggyapot')) OR
        (pav.shop_product_id = 4 AND ao.value IN ('Kőzetgyapot', 'EPS')) OR
        (pav.shop_product_id = 5 AND ao.value IN ('Grafit', 'XPS'))
    )
ON DUPLICATE KEY UPDATE
    updated_at = NOW();
```

## Method 2: Laravel Tinker (Recommended)

This method is safer and uses Eloquent relationships:

```bash
php artisan tinker
```

Then run:

```php
use App\Models\Shop\Product;
use App\Models\Shop\Attribute;

$products = Product::take(10)->get();
$attrs = Attribute::with('options')->get()->keyBy('id');

foreach ($products as $product) {
    // Magasság (Height)
    $product->attributeValues()->updateOrCreate(
        ['shop_attribute_id' => 1],
        ['number_value' => rand(50, 150)]
    );

    // Szélesség (Width)
    $product->attributeValues()->updateOrCreate(
        ['shop_attribute_id' => 2],
        ['number_value' => rand(30, 100)]
    );

    // Hosszúság (Length)
    $product->attributeValues()->updateOrCreate(
        ['shop_attribute_id' => 3],
        ['number_value' => rand(100, 300)]
    );

    // Súly (Weight)
    $product->attributeValues()->updateOrCreate(
        ['shop_attribute_id' => 5],
        ['number_value' => rand(20, 100) / 10]
    );

    // Szín (Color - select)
    $colorAttr = $attrs->get(6);
    if ($colorAttr) {
        $av = $product->attributeValues()->updateOrCreate(
            ['shop_attribute_id' => 6]
        );
        $av->options()->sync([$colorAttr->options->random()->id]);
    }

    // Kiszerelés (Packaging - select)
    $packagingAttr = $attrs->get(7);
    if ($packagingAttr) {
        $av = $product->attributeValues()->updateOrCreate(
            ['shop_attribute_id' => 7]
        );
        $av->options()->sync([$packagingAttr->options->random()->id]);
    }

    // Anyag (Material - multiselect, 1-3 materials)
    $materialAttr = $attrs->get(8);
    if ($materialAttr) {
        $av = $product->attributeValues()->updateOrCreate(
            ['shop_attribute_id' => 8]
        );
        $av->options()->sync($materialAttr->options->random(rand(1,3))->pluck('id'));
    }
}

echo 'Bulk attributes set for ' . $products->count() . ' products';
```

### One-Liner for Quick Testing

```bash
php artisan tinker --execute="use App\Models\Shop\Product; use App\Models\Shop\Attribute; \$products = Product::take(10)->get(); \$attrs = Attribute::with('options')->get()->keyBy('id'); foreach (\$products as \$product) { \$product->attributeValues()->updateOrCreate(['shop_attribute_id' => 1], ['number_value' => rand(50, 150)]); \$product->attributeValues()->updateOrCreate(['shop_attribute_id' => 2], ['number_value' => rand(30, 100)]); \$product->attributeValues()->updateOrCreate(['shop_attribute_id' => 5], ['number_value' => rand(20, 100) / 10]); \$colorAttr = \$attrs->get(6); if (\$colorAttr) { \$av = \$product->attributeValues()->updateOrCreate(['shop_attribute_id' => 6]); \$av->options()->sync([\$colorAttr->options->random()->id]); } \$materialAttr = \$attrs->get(8); if (\$materialAttr) { \$av = \$product->attributeValues()->updateOrCreate(['shop_attribute_id' => 8]); \$av->options()->sync(\$materialAttr->options->random(rand(1,3))->pluck('id')); } } echo 'Bulk attributes set for ' . \$products->count() . ' products';"
```

## Method 3: Seeder Class

Create a dedicated seeder for testing:

```bash
php artisan make:seeder ProductAttributeTestSeeder
```

Content of `database/seeders/ProductAttributeTestSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Shop\Attribute;
use App\Models\Shop\Product;
use Illuminate\Database\Seeder;

class ProductAttributeTestSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::take(10)->get();
        $attributes = Attribute::with('options')->get()->keyBy('id');

        foreach ($products as $product) {
            // Number attributes
            $this->setNumberAttribute($product, 1, rand(50, 150)); // Magasság
            $this->setNumberAttribute($product, 2, rand(30, 100));  // Szélesség
            $this->setNumberAttribute($product, 3, rand(100, 300)); // Hosszúság
            $this->setNumberAttribute($product, 5, rand(20, 100) / 10); // Súly

            // Select attributes
            $this->setSelectAttribute($product, 6, $attributes); // Szín
            $this->setSelectAttribute($product, 7, $attributes); // Kiszerelés

            // Multiselect attributes
            $this->setMultiSelectAttribute($product, 8, $attributes, rand(1, 3)); // Anyag
        }

        $this->command->info("Set attributes for {$products->count()} products");
    }

    private function setNumberAttribute(Product $product, int $attributeId, float $value): void
    {
        $product->attributeValues()->updateOrCreate(
            ['shop_attribute_id' => $attributeId],
            ['number_value' => $value]
        );
    }

    private function setSelectAttribute(Product $product, int $attributeId, $attributes): void
    {
        $attribute = $attributes->get($attributeId);

        if ($attribute && $attribute->options->isNotEmpty()) {
            $av = $product->attributeValues()->updateOrCreate(
                ['shop_attribute_id' => $attributeId]
            );
            $av->options()->sync([$attribute->options->random()->id]);
        }
    }

    private function setMultiSelectAttribute(Product $product, int $attributeId, $attributes, int $count): void
    {
        $attribute = $attributes->get($attributeId);

        if ($attribute && $attribute->options->isNotEmpty()) {
            $av = $product->attributeValues()->updateOrCreate(
                ['shop_attribute_id' => $attributeId]
            );
            $optionIds = $attribute->options->random(min($count, $attribute->options->count()))->pluck('id');
            $av->options()->sync($optionIds);
        }
    }
}
```

Run with:

```bash
php artisan db:seed --class=ProductAttributeTestSeeder
```

## Verification Queries

Check what was created:

```sql
-- Count attributes per product
SELECT
    p.id,
    p.name,
    COUNT(pav.id) as attribute_count
FROM shop_products p
LEFT JOIN shop_product_attribute_values pav ON p.id = pav.shop_product_id
GROUP BY p.id, p.name
ORDER BY p.id
LIMIT 10;

-- View all attributes for a specific product
SELECT
    a.name as attribute_name,
    a.type,
    a.unit,
    pav.text_value,
    pav.number_value,
    pav.boolean_value,
    GROUP_CONCAT(ao.value SEPARATOR ', ') as options
FROM shop_product_attribute_values pav
JOIN shop_attributes a ON pav.shop_attribute_id = a.id
LEFT JOIN shop_product_attribute_value_options pavo ON pav.id = pavo.shop_product_attribute_value_id
LEFT JOIN shop_attribute_options ao ON pavo.shop_attribute_option_id = ao.id
WHERE pav.shop_product_id = 1
GROUP BY a.name, a.type, a.unit, pav.text_value, pav.number_value, pav.boolean_value;
```

Or use Tinker:

```bash
php artisan tinker --execute="use App\Models\Shop\Product; \$p = Product::with('attributeValues.attribute', 'attributeValues.options')->first(); \$p->attributeValues->each(function(\$av) { echo \$av->attribute->name . ': '; if (\$av->attribute->type === 'number') echo \$av->number_value . ' ' . \$av->attribute->unit; elseif (in_array(\$av->attribute->type, ['select', 'multiselect'])) echo \$av->options->pluck('value')->join(', '); echo PHP_EOL; });"
```

## Notes

- Always use `updateOrCreate()` to avoid duplicates
- For select/multiselect, use `sync()` on the options relationship
- The `ON DUPLICATE KEY UPDATE` in SQL handles updates safely
- Number values are stored as `decimal(10,2)` in the database
- Empty/null values are automatically cleaned up by EditProduct page
