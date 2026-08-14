<?php

namespace App\Filament\Clusters\Products\Resources\Categories\Pages;

use App\Filament\Clusters\Products\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the primary unit for this category
        $primaryUnit = $this->record->units()->wherePivot('is_primary', true)->first();
        $data['primary_unit_id'] = $primaryUnit?->id;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove primary_unit_id as it's not a real column
        unset($data['primary_unit_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $primaryUnitId = $this->data['primary_unit_id'] ?? null;

        // Set all units to not primary first
        $this->record->units()->updateExistingPivot(
            $this->record->units->pluck('id')->toArray(),
            ['is_primary' => false]
        );

        if ($primaryUnitId) {
            // Set the selected unit as primary
            $this->record->units()->updateExistingPivot($primaryUnitId, ['is_primary' => true]);
        }
    }
}
