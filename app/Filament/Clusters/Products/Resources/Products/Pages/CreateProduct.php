<?php

namespace App\Filament\Clusters\Products\Resources\Products\Pages;

use App\Filament\Clusters\Products\Resources\Products\ProductResource;
use App\Models\Shop\Attribute;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove primary_unit_id as it's not a real column
        unset($data['primary_unit_id']);

        // Remove attribute fields as they're not real columns
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                unset($data[$key]);
            }
        }

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

        // Save attribute values
        $this->saveAttributeValues();
    }

    protected function saveAttributeValues(): void
    {
        foreach ($this->data as $key => $value) {
            if (! str_starts_with($key, 'attribute_')) {
                continue;
            }

            // Skip if value is null or empty
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            $attributeId = (int) str_replace('attribute_', '', $key);
            $attribute = Attribute::find($attributeId);

            if (! $attribute) {
                continue;
            }

            // Create or update attribute value
            $attributeValue = $this->record->attributeValues()->updateOrCreate(
                ['shop_attribute_id' => $attributeId],
                match ($attribute->type) {
                    'text' => ['text_value' => $value],
                    'number' => ['number_value' => $value],
                    'boolean' => ['boolean_value' => (bool) $value],
                    'select' => ['text_value' => null], // Options handled separately
                    'multiselect' => ['text_value' => null], // Options handled separately
                    default => [],
                }
            );

            // Handle select/multiselect options
            if (in_array($attribute->type, ['select', 'multiselect'])) {
                $optionIds = is_array($value) ? $value : [$value];
                $attributeValue->options()->sync($optionIds);
            }
        }
    }
}
