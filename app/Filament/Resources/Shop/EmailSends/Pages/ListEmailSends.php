<?php

namespace App\Filament\Resources\Shop\EmailSends\Pages;

use App\Filament\Resources\Shop\EmailSends\EmailSendResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailSends extends ListRecords
{
    protected static string $resource = EmailSendResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
