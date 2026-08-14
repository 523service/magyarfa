<?php

namespace App\Filament\Clusters\Products\Resources\Units\Schemas;

use App\Models\Shop\Unit;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UnitForm
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
                                    ->label('Név')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Unit::class, 'slug', ignoreRecord: true),

                                TextInput::make('label')
                                    ->label('Megjelenített név')
                                    ->helperText('Pl. Négyzetméter, Bála')
                                    ->maxLength(50),

                                TextInput::make('label_short')
                                    ->label('Rövid jelölés')
                                    ->helperText('Pl. m², bála, fm')
                                    ->maxLength(20),

                                TextInput::make('sort_order')
                                    ->label('Sorrend')
                                    ->numeric()
                                    ->default(0)
                                    ->rules(['integer', 'min:0']),

                                Toggle::make('is_base_unit')
                                    ->label('Alap egység')
                                    ->helperText('Alap egységek: m², fm, db, kg stb. – ezekhez van kötve az ár.')
                                    ->default(false),
                            ]),
                    ])
                    ->columnSpan(['lg' => fn (?Unit $record) => $record === null ? 3 : 2]),
                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->state(fn (Unit $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Unit $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Unit $record) => $record === null),
            ])
            ->columns(3);
    }
}
