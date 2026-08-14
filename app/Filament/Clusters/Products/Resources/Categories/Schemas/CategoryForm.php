<?php

namespace App\Filament\Clusters\Products\Resources\Categories\Schemas;

use App\Models\Shop\Category;
use App\Models\Shop\Unit;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'slug', ignoreRecord: true),
                            ]),

                        Select::make('parent_id')
                            ->relationship('parent', 'name', fn (Builder $query) => $query->where('parent_id', null))
                            ->searchable()
                            ->placeholder('Select parent category'),

                        Toggle::make('is_visible')
                            ->label('Visibility')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('Featured category')
                            ->helperText('Show this category in the featured categories block on the homepage.')
                            ->default(false),

                        RichEditor::make('description'),

                        KeyValue::make('meta')
                            ->label('Meta adatok')
                            ->helperText('Egyéni adatok a kategóriakártyához. Pl. icon_path: SVG útvonal, featured_label: egyéni felirat.')
                            ->keyLabel('Kulcs')
                            ->valueLabel('Érték')
                            ->reorderable()
                            ->columnSpanFull(),

                        Section::make('Default Units')
                            ->schema([
                                Select::make('units')
                                    ->label('Units')
                                    ->relationship('units', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->live()
                                    ->helperText('Default units for products in this category.'),

                                Select::make('primary_unit_id')
                                    ->label('Primary Unit')
                                    ->options(fn () => Unit::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Default primary unit for products without their own primary unit.'),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => fn (?Category $record) => $record === null ? 3 : 2]),
                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->state(fn (Category $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Category $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Category $record) => $record === null),
            ])
            ->columns(3);
    }
}
