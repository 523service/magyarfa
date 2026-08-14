<?php

namespace App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages;

use App\Filament\Clusters\Products\Resources\MaterialBasePrices\MaterialBasePriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialBasePrice extends EditRecord
{
    protected static string $resource = MaterialBasePriceResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
