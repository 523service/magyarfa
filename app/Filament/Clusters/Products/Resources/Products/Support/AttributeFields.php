<?php

namespace App\Filament\Clusters\Products\Resources\Products\Support;

use App\Models\Shop\Attribute;
use App\Models\Shop\Product;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AttributeFields
{
    /**
     * Build dynamic form fields for all visible attributes.
     *
     * @return array<int, Field>
     */
    public static function buildFields(): array
    {
        $attributes = Attribute::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $fields = [];

        foreach ($attributes as $attribute) {
            $fieldName = "attribute_{$attribute->id}";

            match ($attribute->type) {
                'text' => $fields[] = TextInput::make($fieldName)
                    ->label($attribute->name . ($attribute->unit ? " ({$attribute->unit})" : ''))
                    ->maxLength(255)
                    ->required($attribute->is_required),

                'number' => $fields[] = TextInput::make($fieldName)
                    ->label($attribute->name . ($attribute->unit ? " ({$attribute->unit})" : ''))
                    ->numeric()
                    ->required($attribute->is_required),

                'select' => $fields[] = Select::make($fieldName)
                    ->label($attribute->name)
                    ->options($attribute->options->pluck('value', 'id'))
                    ->searchable()
                    ->required($attribute->is_required),

                'multiselect' => $fields[] = Select::make($fieldName)
                    ->label($attribute->name)
                    ->options($attribute->options->pluck('value', 'id'))
                    ->multiple()
                    ->searchable()
                    ->required($attribute->is_required),

                'boolean' => $fields[] = Toggle::make($fieldName)
                    ->label($attribute->name)
                    ->required($attribute->is_required),

                default => null,
            };
        }

        return $fields;
    }

    /**
     * Save attribute values from form data to the given product.
     * Only processes keys starting with "attribute_". Empty/null values delete the record.
     */
    public static function saveAttributeValues(Product $product, array $data): void
    {
        foreach ($data as $key => $value) {
            if (! str_starts_with($key, 'attribute_')) {
                continue;
            }

            $attributeId = (int) str_replace('attribute_', '', $key);
            $attribute = Attribute::find($attributeId);

            if (! $attribute) {
                continue;
            }

            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $product->attributeValues()->where('shop_attribute_id', $attributeId)->delete();

                continue;
            }

            $attributeValue = $product->attributeValues()->updateOrCreate(
                ['shop_attribute_id' => $attributeId],
                match ($attribute->type) {
                    'text' => ['text_value' => $value],
                    'number' => ['number_value' => (float) $value],
                    'boolean' => ['boolean_value' => (bool) $value],
                    'select' => ['text_value' => null],
                    'multiselect' => ['text_value' => null],
                    default => [],
                }
            );

            if (in_array($attribute->type, ['select', 'multiselect'])) {
                $optionIds = is_array($value) ? $value : [$value];
                $attributeValue->options()->sync($optionIds);
            }
        }
    }
}
