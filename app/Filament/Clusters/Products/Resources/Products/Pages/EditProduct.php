<?php

namespace App\Filament\Clusters\Products\Resources\Products\Pages;

use App\Filament\Clusters\Products\Resources\Products\ProductResource;
use App\Models\Shop\Attribute;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the primary unit for this product
        $primaryUnit = $this->record->units()->wherePivot('is_primary', true)->first();
        $data['primary_unit_id'] = $primaryUnit?->id;

        // Load existing attribute values
        $attributeValues = $this->record->attributeValues()->with(['attribute', 'options'])->get();

        foreach ($attributeValues as $attributeValue) {
            $attribute = $attributeValue->attribute;
            $fieldName = "attribute_{$attribute->id}";

            $data[$fieldName] = match ($attribute->type) {
                'text' => $attributeValue->text_value,
                'number' => $attributeValue->number_value,
                'boolean' => $attributeValue->boolean_value,
                'select' => $attributeValue->options->first()?->id,
                'multiselect' => $attributeValue->options->pluck('id')->toArray(),
                default => null,
            };
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

        // Save attribute values
        $this->saveAttributeValues();
    }

    protected function saveAttributeValues(): void
    {
        foreach ($this->data as $key => $value) {
            if (! str_starts_with($key, 'attribute_')) {
                continue;
            }

            $attributeId = (int) str_replace('attribute_', '', $key);
            $attribute = Attribute::find($attributeId);

            if (! $attribute) {
                continue;
            }

            // Delete if value is null/empty
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $this->record->attributeValues()->where('shop_attribute_id', $attributeId)->delete();

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
