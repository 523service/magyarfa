<?php

namespace App\Filament\Clusters\Products\Resources\Products\RelationManagers;

use App\Jobs\ScrapeCompetitorLinkJob;
use App\Models\Shop\CompetitorLink;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompetitorLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'competitorLinks';

    protected static ?string $title = 'Konkurencia linkek';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('url')
                ->label('Konkurencia URL')
                ->placeholder('https://...')
                ->url()
                ->required()
                ->maxLength(2048)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('competitor_name')
                    ->label('Konkurens')
                    ->badge()
                    ->color('info'),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(55)
                    ->copyable()
                    ->url(fn (CompetitorLink $record): string => $record->url, true),

                TextColumn::make('scraped_price')
                    ->label('Ár')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 0, ',', ' ') . ' Ft' : '—')
                    ->sortable(),

                TextColumn::make('scraped_sale_price')
                    ->label('Akciós ár')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 0, ',', ' ') . ' Ft' : '—')
                    ->color('danger'),

                TextColumn::make('last_scraped_at')
                    ->label('Utolsó frissítés')
                    ->since()
                    ->placeholder('Még nem scrape-elve'),

                TextColumn::make('scrape_status')
                    ->label('Státusz')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Sikeres',
                        'failed' => 'Hibás',
                        default => 'Függőben',
                    })
                    ->description(
                        fn (CompetitorLink $record): ?string => $record->scrape_status === 'failed'
                        ? $record->scrape_error
                        : null
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Link hozzáadása'),

                Action::make('scrapeAll')
                    ->label('Összes frissítése')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (): void {
                        $links = $this->getOwnerRecord()->competitorLinks()->get();
                        $links->each(function (CompetitorLink $link, int $index): void {
                            ScrapeCompetitorLinkJob::dispatch($link)
                                ->delay(now()->addSeconds($index * 5));
                        });

                        Notification::make()
                            ->title("{$links->count()} scraping job sorba állítva")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->recordActions([
                Action::make('scrapeNow')
                    ->label('Frissítés')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (CompetitorLink $record): void {
                        ScrapeCompetitorLinkJob::dispatchSync($record);

                        $record->refresh();

                        $notification = Notification::make()
                            ->title($record->scrape_status === 'success' ? 'Scraping sikeres' : 'Scraping sikertelen');

                        if ($record->scrape_status === 'success') {
                            $notification->success();
                        } else {
                            $notification->danger()->body($record->scrape_error);
                        }

                        $notification->send();
                    }),

                Action::make('viewDescription')
                    ->label('Leírás')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading(fn (CompetitorLink $record): string => "Leírás – {$record->competitor_name}")
                    ->modalContent(fn (CompetitorLink $record) => view(
                        'filament.competitor-description-modal',
                        ['link' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Bezárás'),

                EditAction::make()
                    ->label('Szerkesztés'),

                DeleteAction::make()
                    ->label('Törlés'),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
