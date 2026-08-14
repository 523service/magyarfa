<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates\Pages;

use App\Filament\Clusters\Products\Resources\SystemTemplates\SystemTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemTemplate extends EditRecord
{
    protected static string $resource = SystemTemplateResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
