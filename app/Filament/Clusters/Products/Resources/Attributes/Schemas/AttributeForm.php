<?php

namespace App\Filament\Clusters\Products\Resources\Attributes\Schemas;

use App\Models\Shop\Attribute;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
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
                                    ->unique(Attribute::class, 'slug', ignoreRecord: true),
                            ]),

                        Grid::make()
                            ->schema([
                                Select::make('type')
                                    ->required()
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'select' => 'Select',
                                        'multiselect' => 'Multiselect',
                                        'boolean' => 'Boolean',
                                    ])
                                    ->default('text'),

                                TextInput::make('unit')
                                    ->maxLength(255)
                                    ->placeholder('e.g., cm, kg, m'),

                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),

                        Grid::make()
                            ->schema([
                                Toggle::make('is_required')
                                    ->label('Required')
                                    ->default(false),

                                Toggle::make('is_filterable')
                                    ->label('Filterable')
                                    ->default(false),

                                Toggle::make('is_visible')
                                    ->label('Visible')
                                    ->default(true),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?Attribute $record) => $record === null ? 3 : 2]),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->state(fn (Attribute $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Attribute $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Attribute $record) => $record === null),
            ])
            ->columns(3);
    }
}
