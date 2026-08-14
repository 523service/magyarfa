<?php

namespace App\Filament\Clusters\Products\Resources\Categories\Pages;

use App\Filament\Clusters\Products\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove primary_unit_id as it's not a real column
        unset($data['primary_unit_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $primaryUnitId = $this->data['primary_unit_id'] ?? null;

        if ($primaryUnitId) {
            // Set all units to not primary first
            $this->record->units()->updateExistingPivot(
                $this->record->units->pluck('id')->toArray(),
                ['is_primary' => false]
            );

            // Set the selected unit as primary
            $this->record->units()->updateExistingPivot($primaryUnitId, ['is_primary' => true]);
        }
    }
}
