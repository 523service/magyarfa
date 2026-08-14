<?php

namespace App\Filament\Resources\Feedbacks\Pages;

use App\Filament\Resources\Feedbacks\FeedbackResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListFeedbacks extends ListRecords
{
    protected static string $resource = FeedbackResource::class;

    protected function getActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('Mind'),
            'new' => Tab::make('Új')->query(fn ($query) => $query->where('status', 'new')),
            'in_progress' => Tab::make('Folyamatban')->query(fn ($query) => $query->where('status', 'in_progress')),
            'resolved' => Tab::make('Megoldva')->query(fn ($query) => $query->where('status', 'resolved')),
            'closed' => Tab::make('Lezárva')->query(fn ($query) => $query->where('status', 'closed')),
        ];
    }
}
