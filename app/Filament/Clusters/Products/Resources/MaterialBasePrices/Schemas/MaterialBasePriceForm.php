<?php

namespace App\Filament\Clusters\Products\Resources\MaterialBasePrices\Schemas;

use App\Models\Shop\MaterialBasePrice;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MaterialBasePriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alapár adatok')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Megnevezés')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(MaterialBasePrice::class, 'slug', ignoreRecord: true),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('price_per_unit')
                                    ->label('Egységár (Ft)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('Ft'),

                                TextInput::make('attribute_slug')
                                    ->label('Attribútum slug')
                                    ->maxLength(100)
                                    ->placeholder('pl. vastagsag, meret_liter')
                                    ->helperText('A termék melyik attribútumából olvasson mennyiséget'),

                                TextInput::make('unit_label')
                                    ->label('Egységcímke')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('pl. cm, liter, kg')
                                    ->helperText('Megjelenítéshez (pl. Ft/cm)'),
                            ]),

                        Textarea::make('description')
                            ->label('Leírás')
                            ->rows(2)
                            ->nullable(),

                        Toggle::make('is_active')
                            ->label('Aktív')
                            ->default(true),
                    ]),
            ]);
    }
}
