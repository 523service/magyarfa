<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductImportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call('shop:import-products');
    }
}
