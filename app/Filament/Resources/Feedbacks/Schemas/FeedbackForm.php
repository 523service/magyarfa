<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use App\Enums\FeedbackStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Állapot')
                    ->options(FeedbackStatus::class)
                    ->required(),
            ]);
    }
}
