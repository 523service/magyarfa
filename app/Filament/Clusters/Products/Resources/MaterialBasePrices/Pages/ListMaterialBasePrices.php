<?php

namespace App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages;

use App\Filament\Clusters\Products\Resources\MaterialBasePrices\MaterialBasePriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialBasePrices extends ListRecords
{
    protected static string $resource = MaterialBasePriceResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
