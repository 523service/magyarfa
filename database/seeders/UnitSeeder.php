<?php

namespace Database\Seeders;

use App\Models\Shop\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * The predefined units to seed.
     *
     * @var array<int, array{name: string, slug: string, label: string, label_short: string, is_base_unit: bool, sort_order: int}>
     */
    protected array $units = [
        ['name' => 'db',         'slug' => 'db',          'label' => 'Darab',       'label_short' => 'db',       'is_base_unit' => true,  'sort_order' => 1],
        ['name' => 'Folyóméter', 'slug' => 'fm',          'label' => 'Folyóméter',  'label_short' => 'fm',       'is_base_unit' => true,  'sort_order' => 4],
        ['name' => 'm²',         'slug' => 'm2',          'label' => 'Négyzetméter', 'label_short' => 'm²',       'is_base_unit' => true,  'sort_order' => 2],
        ['name' => 'm³',         'slug' => 'm3',          'label' => 'Köbméter',    'label_short' => 'm³',       'is_base_unit' => true,  'sort_order' => 3],
        ['name' => 'Folyóméter', 'slug' => 'folyometer',  'label' => 'Folyóméter',  'label_short' => 'fm',       'is_base_unit' => true,  'sort_order' => 4],
        ['name' => 'kg',         'slug' => 'kg',          'label' => 'Kilogramm',   'label_short' => 'kg',       'is_base_unit' => true,  'sort_order' => 5],
        ['name' => 'Liter',      'slug' => 'liter',       'label' => 'Liter',       'label_short' => 'L',        'is_base_unit' => true,  'sort_order' => 6],
        ['name' => 'Bála',       'slug' => 'bala',        'label' => 'Bála',        'label_short' => 'bála',     'is_base_unit' => false, 'sort_order' => 7],
        ['name' => 'Zsák',       'slug' => 'zsak',        'label' => 'Zsák',        'label_short' => 'zsák',     'is_base_unit' => false, 'sort_order' => 8],
        ['name' => 'Csomag',     'slug' => 'csomag',      'label' => 'Csomag',      'label_short' => 'csomag',   'is_base_unit' => false, 'sort_order' => 9],
        ['name' => 'Raklap',     'slug' => 'raklap',      'label' => 'Raklap',      'label_short' => 'raklap',   'is_base_unit' => false, 'sort_order' => 10],
        ['name' => 'Tekercs',    'slug' => 'tekercs',     'label' => 'Tekercs',     'label_short' => 'tekercs',  'is_base_unit' => false, 'sort_order' => 11],
        ['name' => 'Szál',       'slug' => 'szal',        'label' => 'Szál',        'label_short' => 'szál',     'is_base_unit' => false, 'sort_order' => 12],
        ['name' => 'Kaloda',     'slug' => 'kaloda',      'label' => 'Kaloda',      'label_short' => 'kaloda',   'is_base_unit' => false, 'sort_order' => 13],
        ['name' => 'Vödör',      'slug' => 'vodor',       'label' => 'Vödör',       'label_short' => 'vödör',    'is_base_unit' => false, 'sort_order' => 14],
        ['name' => 'Pár',        'slug' => 'par',         'label' => 'Pár',         'label_short' => 'pár',      'is_base_unit' => false, 'sort_order' => 15],
        ['name' => 'Karton',     'slug' => 'karton',      'label' => 'Karton',      'label_short' => 'karton',   'is_base_unit' => false, 'sort_order' => 16],
        ['name' => 'Tábla',      'slug' => 'tabla',       'label' => 'Tábla',       'label_short' => 'tábla',    'is_base_unit' => false, 'sort_order' => 17],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->units as $unit) {
            Unit::updateOrCreate(
                ['slug' => $unit['slug']],
                [
                    'name' => $unit['name'],
                    'label' => $unit['label'],
                    'label_short' => $unit['label_short'],
                    'is_base_unit' => $unit['is_base_unit'],
                    'sort_order' => $unit['sort_order'],
                ]
            );
        }
    }
}
