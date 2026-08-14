<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates\RelationManagers;

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

class SystemTemplateItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Összetevők';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('material_price_id')
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

            Select::make('quantity_type')
                ->label('Mennyiség típusa')
                ->options([
                    'fixed' => 'Fix mennyiség',
                    'product_thickness_cm' => 'Termék vastagság (cm)',
                ])
                ->required()
                ->default('fixed')
                ->live(),

            Grid::make(2)
                ->schema([
                    TextInput::make('quantity_value')
                        ->label('Fix mennyiség értéke')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.0001)
                        ->helperText('Fixáras anyagnál (pl. ragasztó, háló)')
                        ->visible(fn ($get) => $get('quantity_type') === 'fixed'),

                    TextInput::make('sort_order')
                        ->label('Sorrend')
                        ->integer()
                        ->default(0),
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
                    ->label('Összetevő'),

                TextColumn::make('materialPrice.name')
                    ->label('Anyag alapár'),

                TextColumn::make('materialPrice.price_per_unit')
                    ->label('Egységár')
                    ->formatStateUsing(
                        fn ($state, $record) => number_format((float) $state, 0, ',', ' ')
                            . " Ft/{$record->materialPrice?->unit_label}"
                    ),

                TextColumn::make('quantity_type')
                    ->label('Menny. típus')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'fixed' => 'Fix',
                        'product_thickness_cm' => 'Vastagság (cm)',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'fixed' => 'info',
                        'product_thickness_cm' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('quantity_value')
                    ->label('Fix menny.')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('auto'),
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
