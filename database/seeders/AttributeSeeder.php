<?php

namespace Database\Seeders;

use App\Models\Shop\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Magasság',
                'slug' => 'magassag',
                'type' => 'number',
                'unit' => 'cm',
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 1,
                'options' => [],
            ],
            [
                'name' => 'Szélesség',
                'slug' => 'szelesseg',
                'type' => 'number',
                'unit' => 'cm',
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 2,
                'options' => [],
            ],
            [
                'name' => 'Hosszúság',
                'slug' => 'hosszusag',
                'type' => 'number',
                'unit' => 'cm',
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 3,
                'options' => [],
            ],
            [
                'name' => 'Mélység',
                'slug' => 'melyseg',
                'type' => 'number',
                'unit' => 'cm',
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 4,
                'options' => [],
            ],
            [
                'name' => 'Súly',
                'slug' => 'suly',
                'type' => 'number',
                'unit' => 'kg',
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 5,
                'options' => [],
            ],
            [
                'name' => 'Szín',
                'slug' => 'szin',
                'type' => 'select',
                'unit' => null,
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 6,
                'options' => ['Fehér', 'Fekete', 'Piros', 'Kék', 'Zöld', 'Sárga', 'Szürke', 'Barna'],
            ],
            [
                'name' => 'Kiszerelés',
                'slug' => 'kiszereles',
                'type' => 'select',
                'unit' => null,
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 7,
                'options' => ['Darabos', 'Csomag (5db)', 'Csomag (10db)', 'Dobozos', 'Palettás'],
            ],
            [
                'name' => 'Anyag',
                'slug' => 'anyag',
                'type' => 'multiselect',
                'unit' => null,
                'is_required' => false,
                'is_filterable' => true,
                'is_visible' => true,
                'sort_order' => 8,
                'options' => ['EPS', 'XPS', 'Grafit', 'Polisztirol', 'Üveggyapot', 'Kőzetgyapot'],
            ],
        ];

        foreach ($attributes as $attributeData) {
            $options = $attributeData['options'];
            unset($attributeData['options']);

            $attribute = Attribute::create($attributeData);

            // Create options for select/multiselect types
            if (! empty($options)) {
                foreach ($options as $index => $optionValue) {
                    $attribute->options()->create([
                        'value' => $optionValue,
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }
    }
}
