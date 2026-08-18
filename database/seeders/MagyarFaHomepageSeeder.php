<?php

namespace Database\Seeders;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use Illuminate\Database\Seeder;

/**
 * Seeds the demo "Magyar Fa" categories and homepage products used by the
 * redesigned kezdőlap (see .vibe/doc/magyarfa-design-style-guide.md).
 *
 * Idempotent: safe to re-run, matches existing rows by slug instead of
 * duplicating them. Run standalone — NOT wired into DatabaseSeeder, which
 * wipes storage and rebuilds unrelated demo data on every run:
 *
 *   php artisan db:seed --class=MagyarFaHomepageSeeder
 */
class MagyarFaHomepageSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'fenyo-fureszaru',
                'name' => 'Fenyő fűrészáru',
                'description' => 'Tetőszerkezeti faanyagok, gerendák, pallók, deszkák, lécek',
                'position' => 1,
                'icon_path' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
            ],
            [
                'slug' => 'gyalult-faaru',
                'name' => 'Gyalult faáru',
                'description' => 'Lambéria, gyalult deszkák kül- és beltérre',
                'position' => 2,
                'icon_path' => 'M4 6h16M4 12h16M4 18h16',
            ],
            [
                'slug' => 'osb-lemez',
                'name' => 'OSB lemez',
                'description' => 'OSB és nútféderes OSB lemezek különböző felhasználásra',
                'position' => 3,
                'icon_path' => 'M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z',
            ],
        ];

        $categoryModels = [];

        foreach ($categories as $data) {
            $categoryModels[$data['slug']] = Category::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'position' => $data['position'],
                    'is_visible' => true,
                    'is_featured' => true,
                    'meta' => [
                        'icon_path' => $data['icon_path'],
                        'featured_label' => $data['name'],
                    ],
                ]
            );
        }

        $unitByName = Unit::whereIn('name', ['db', 'Folyóméter'])->get()->keyBy('name');
        $dbUnit = $unitByName->get('db');
        $fmUnit = $unitByName->get('Folyóméter');

        $products = [
            [
                'slug' => 'gerenda-100x100',
                'name' => 'Gerenda 100×100 mm',
                'description' => '100×100 mm, 5 fm-ig',
                'price' => 1250,
                'unit' => $fmUnit,
                'categories' => ['fenyo-fureszaru'],
                'position' => 1,
            ],
            [
                'slug' => 'epito-deszka-25x100',
                'name' => 'Építő deszka 25×100 mm',
                'description' => '25×100 mm, 5 fm-ig',
                'price' => 720,
                'unit' => $fmUnit,
                'categories' => ['fenyo-fureszaru'],
                'position' => 2,
            ],
            [
                'slug' => 'tetolec-30x50',
                'name' => 'Tetőléc 30×50 mm',
                'description' => '30×50 mm, 5 fm-ig',
                'price' => 380,
                'unit' => $fmUnit,
                'categories' => ['fenyo-fureszaru'],
                'position' => 3,
            ],
            [
                'slug' => 'osb-lemez-12mm',
                'name' => 'OSB lemez 12 mm',
                'description' => '12 mm, 2500×1250 mm',
                'price' => 4690,
                'unit' => $dbUnit,
                'categories' => ['osb-lemez'],
                'position' => 4,
            ],
            [
                'slug' => 'nutfederes-osb-18mm',
                'name' => 'Nútféderes OSB 18 mm',
                'description' => '18 mm, 2500×675 mm',
                'price' => 5990,
                'unit' => $dbUnit,
                'categories' => ['osb-lemez'],
                'position' => 5,
            ],
        ];

        foreach ($products as $data) {
            /** @var Product $product */
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'qty' => 500,
                    'featured' => false,
                    'is_visible' => true,
                    'is_homepage' => true,
                    'is_on_sale' => false,
                    'price' => $data['price'],
                    'pricing_mode' => 'manual',
                    'type' => 'deliverable',
                    'requires_shipping' => true,
                    'published_at' => now(),
                    'position' => $data['position'],
                ]
            );

            $categoryIds = collect($data['categories'])
                ->map(fn (string $slug) => $categoryModels[$slug]->id)
                ->all();
            $product->categories()->syncWithoutDetaching($categoryIds);

            if ($data['unit']) {
                $product->units()->syncWithoutDetaching([
                    $data['unit']->id => ['is_primary' => true],
                ]);
            }
        }

        $this->command->info('Magyar Fa demo categories and homepage products seeded.');
    }
}
