<?php

namespace App\Filament\Clusters\Products\Resources\Categories\Tables;

use App\Models\Shop\Category;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('units_count')
                    ->label('Units')
                    ->counts('units')
                    ->sortable(),
                TextColumn::make('primary_unit')
                    ->label('Primary Unit')
                    ->state(function (Category $record): ?string {
                        $primaryUnit = $record->units()->wherePivot('is_primary', true)->first();

                        return $primaryUnit?->name;
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Last modified at')
                    ->date()
                    ->sortable(),
            ])
            ->reorderable('position')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->action(function (): void {
                        Notification::make()
                            ->title('Tömeges törlés action')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}
