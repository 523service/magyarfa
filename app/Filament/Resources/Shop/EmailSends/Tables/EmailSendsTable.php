<?php

namespace App\Filament\Resources\Shop\EmailSends\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmailSendsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.number')
                    ->label('Rendelés')
                    ->searchable()
                    ->sortable(),
                    //->url(fn ($record) => $record->order ? route('filament.admin.resources.shop/orders.edit', ['record' => $record->order]) : null),

                TextColumn::make('recipient_email')
                    ->label('Címzett')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('subject')
                    ->label('Tárgy')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('sent_at')
                    ->label('Elküldve')
                    ->dateTime('Y. m. d. H:i')
                    ->sortable()
                    ->default('desc'),

                TextColumn::make('opened_at')
                    ->label('Megnyitva')
                    ->dateTime('Y. m. d. H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y. m. d. H:i') : 'Nem nyitva'),

                TextColumn::make('open_count')
                    ->label('Megnyitások')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('click_count')
                    ->label('Kattintások')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('opened')
                    ->label('Megnyitva')
                    ->options([
                        'yes' => 'Megnyitva',
                        'no' => 'Nem nyitva',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'yes',
                            fn (Builder $query): Builder => $query->whereNotNull('opened_at')
                        )->when(
                            $data['value'] === 'no',
                            fn (Builder $query): Builder => $query->whereNull('opened_at')
                        );
                    }),

                Filter::make('sent_at')
                    ->label('Küldés dátuma')
                    ->schema([
                        DatePicker::make('sent_from')
                            ->label('Ettől')
                            ->placeholder(fn ($state): string => now()->subMonth()->format('Y. m. d.')),
                        DatePicker::make('sent_until')
                            ->label('Eddig')
                            ->placeholder(fn ($state): string => now()->format('Y. m. d.')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['sent_from'] ?? null) {
                            $indicators['sent_from'] = 'Ettől: ' . Carbon::parse($data['sent_from'])->format('Y. m. d.');
                        }
                        if ($data['sent_until'] ?? null) {
                            $indicators['sent_until'] = 'Eddig: ' . Carbon::parse($data['sent_until'])->format('Y. m. d.');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('sent_at', 'desc');
    }
}
