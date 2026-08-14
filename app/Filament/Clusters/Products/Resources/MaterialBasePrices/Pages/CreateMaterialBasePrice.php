<?php

namespace App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages;

use App\Filament\Clusters\Products\Resources\MaterialBasePrices\MaterialBasePriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialBasePrice extends CreateRecord
{
    protected static string $resource = MaterialBasePriceResource::class;
}
