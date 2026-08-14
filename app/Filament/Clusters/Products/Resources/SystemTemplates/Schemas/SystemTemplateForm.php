<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates\Schemas;

use App\Models\Shop\SystemTemplate;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SystemTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sablon adatok')
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
                                    ->unique(SystemTemplate::class, 'slug', ignoreRecord: true),
                            ]),

                        Textarea::make('notes')
                            ->label('Megjegyzés')
                            ->rows(2)
                            ->nullable(),

                        Toggle::make('is_active')
                            ->label('Aktív')
                            ->default(true),
                    ]),
            ]);
    }
}
