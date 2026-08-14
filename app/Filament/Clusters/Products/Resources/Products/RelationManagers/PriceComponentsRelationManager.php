<?php

namespace App\Filament\Clusters\Products\Resources\Products\RelationManagers;

use App\Models\Shop\MaterialBasePrice;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'priceComponents';

    protected static ?string $title = 'Ár komponensek';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('material_base_price_id')
                ->label('Anyag alapár')
                ->options(
                    MaterialBasePrice::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($mbp) => [
                            $mbp->id => "{$mbp->name} ({$mbp->price_per_unit} Ft/{$mbp->unit_label})",
                        ])
                )
                ->required()
                ->searchable()
                ->columnSpanFull(),

            TextInput::make('label')
                ->label('Megnevezés')
                ->required()
                ->maxLength(255)
                ->placeholder('pl. EPS 80 lap, TDR40 ragasztótapasz'),

            TextInput::make('sort_order')
                ->label('Sorrend')
                ->integer()
                ->default(0),

            Grid::make(2)
                ->schema([
                    TextInput::make('quantity')
                        ->label('Fix mennyiség')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.0001)
                        ->helperText('Fixáras anyagnál (pl. ragasztó, háló): 1'),

                    TextInput::make('attribute_slug')
                        ->label('Attribútum slug')
                        ->maxLength(100)
                        ->placeholder('pl. vastagsag')
                        ->helperText('Ha kitöltve, a mennyiség a termék attribútumából jön (pl. EPS vastagság).'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label('Komponens'),

                TextColumn::make('materialBasePrice.name')
                    ->label('Anyag alapár'),

                TextColumn::make('materialBasePrice.price_per_unit')
                    ->label('Egységár')
                    ->formatStateUsing(
                        fn ($state, $record) => number_format((float) $state, 0, ',', ' ')
                            . " Ft/{$record->materialBasePrice?->unit_label}"
                    ),

                TextColumn::make('quantity')
                    ->label('Fix menny.')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—'),

                TextColumn::make('attribute_slug')
                    ->label('Attrib. slug')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
