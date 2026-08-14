<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates\Pages;

use App\Filament\Clusters\Products\Resources\SystemTemplates\SystemTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemTemplates extends ListRecords
{
    protected static string $resource = SystemTemplateResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
