<?php

namespace App\Filament\Clusters\Products\Resources\Units\Pages;

use App\Filament\Clusters\Products\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
