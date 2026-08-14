<?php

namespace App\Filament\Resources\Feedbacks\Tables;

use App\Enums\FeedbackStatus;
use App\Filament\Clusters\Products\Resources\Categories\CategoryResource;
use App\Filament\Clusters\Products\Resources\Products\ProductResource;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FeedbacksTable
{
    private static function isProductOrCategoryUrl(string $url): bool
    {
        return str_contains($url, '/termek/') || str_contains($url, '/kategoria/');
    }

    private static function resolveResourceUrl(string $url): ?string
    {
        if (preg_match('~/termek/([^/?]+)~', $url, $matches)) {
            $product = Product::where('slug', $matches[1])->first();

            return $product ? ProductResource::getUrl('edit', ['record' => $product]) : null;
        }

        if (preg_match('~/kategoria/([^/?]+)~', $url, $matches)) {
            $category = Category::where('slug', $matches[1])->first();

            return $category ? CategoryResource::getUrl('edit', ['record' => $category]) : null;
        }

        return null;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Név')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggledHiddenByDefault()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Leírás')
                    ->limit(60)
                    ->tooltip(fn (string $state): string => $state),
                TextColumn::make('status')
                    ->label('Állapot')
                    ->badge()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('Oldal')
                    ->limit(50)
                    ->url(fn ($record): string => $record->url)
                    ->openUrlInNewTab()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Beküldve')
                    ->dateTime('Y.m.d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Állapot')
                    ->options(FeedbackStatus::class),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Megoldva')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => FeedbackStatus::Resolved]))
                    ->visible(fn ($record): bool => $record->status !== FeedbackStatus::Resolved),
                Action::make('open_resource')
                    ->label(
                        fn ($record): string => str_contains($record->url ?? '', '/termek/')
                        ? 'Termék megnyitása'
                        : 'Kategória megnyitása'
                    )
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record): ?string => self::resolveResourceUrl($record->url ?? ''))
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => self::isProductOrCategoryUrl($record->url ?? '')),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                BulkAction::make('resolve')
                    ->label('Megoldva')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->update(['status' => FeedbackStatus::Resolved]))
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
