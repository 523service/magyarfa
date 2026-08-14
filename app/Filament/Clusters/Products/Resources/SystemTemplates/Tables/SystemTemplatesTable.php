<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SystemTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Megnevezés')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('items_count')
                    ->label('Sorok')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('products_count')
                    ->label('Termékek')
                    ->counts('products')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktív')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Módosítva')
                    ->date('Y.m.d')
                    ->sortable(),
            ])
            ->filters([])
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
            ])
            ->defaultSort('name');
    }
}
