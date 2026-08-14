<?php

namespace App\Filament\Resources\Shop\EmailSends\Schemas;

use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class EmailSendForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                TextInput::make('recipient_email')
                                    ->label('Címzett')
                                    ->email()
                                    ->required()
                                    ->disabled(),

                                TextInput::make('subject')
                                    ->label('Tárgy')
                                    ->required()
                                    ->disabled(),

                                TextInput::make('tracking_token')
                                    ->label('Követési token')
                                    ->disabled(),
                            ])
                            ->columns(2),

                        Group::make()
                            ->schema([
                                DateTimePicker::make('sent_at')
                                    ->label('Elküldve')
                                    ->disabled(),

                                DateTimePicker::make('opened_at')
                                    ->label('Megnyitva')
                                    ->disabled(),

                                TextInput::make('open_count')
                                    ->label('Megnyitások száma')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('click_count')
                                    ->label('Kattintások száma')
                                    ->numeric()
                                    ->disabled(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
