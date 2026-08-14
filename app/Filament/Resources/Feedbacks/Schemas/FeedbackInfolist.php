<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Bejelentés adatai')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Név'),
                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->copyable(),
                                    ]),
                                TextEntry::make('description')
                                    ->label('Leírás')
                                    ->columnSpanFull(),
                                TextEntry::make('url')
                                    ->label('Oldal URL')
                                    ->url(fn ($record): string => $record->url)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        Section::make('Státusz & Meta')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Állapot')
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label('Beküldve')
                                    ->dateTime('Y.m.d H:i'),
                                TextEntry::make('user.name')
                                    ->label('Felhasználó')
                                    ->default('Vendég'),
                            ])
                            ->columnSpan(1),

                        Section::make('Eszközadatok')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('device_info.ip')
                                            ->label('IP cím')
                                            ->copyable(),
                                        TextEntry::make('device_info.accept_language')
                                            ->label('Nyelv'),
                                        TextEntry::make('device_info.screen_width')
                                            ->label('Képernyő szélesség')
                                            ->suffix(' px'),
                                        TextEntry::make('device_info.screen_height')
                                            ->label('Képernyő magasság')
                                            ->suffix(' px'),
                                        TextEntry::make('device_info.user_agent')
                                            ->label('User Agent')
                                            ->columnSpanFull()
                                            ->copyable(),
                                    ]),
                            ])
                            ->columnSpan(3)
                            ->collapsible()
                            ->collapsed(),
                    ]),
            ]);
    }
}
