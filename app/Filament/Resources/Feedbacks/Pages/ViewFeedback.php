<?php

namespace App\Filament\Resources\Feedbacks\Pages;

use App\Enums\FeedbackStatus;
use App\Filament\Clusters\Products\Resources\Categories\CategoryResource;
use App\Filament\Clusters\Products\Resources\Products\ProductResource;
use App\Filament\Resources\Feedbacks\FeedbackResource;
use App\Models\Feedback;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewFeedback extends ViewRecord
{
    protected static string $resource = FeedbackResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var Feedback $record */
        $record = $this->getRecord();

        return $record->email
            ? $record->name . ' — ' . $record->email
            : $record->name;
    }

    private function resolveResourceUrl(): ?string
    {
        /** @var Feedback $record */
        $record = $this->getRecord();
        $url = $record->url ?? '';

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

    protected function getActions(): array
    {
        /** @var Feedback $record */
        $record = $this->getRecord();
        $url = $record->url ?? '';

        $resourceLabel = str_contains($url, '/termek/')
            ? 'Termék megnyitása'
            : 'Kategória megnyitása';

        return [
            Action::make('open_resource')
                ->label($resourceLabel)
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn (): ?string => $this->resolveResourceUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => str_contains($url, '/termek/') || str_contains($url, '/kategoria/')),
            Action::make('resolve')
                ->label('Megoldva')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function (): void {
                    $this->getRecord()->update(['status' => FeedbackStatus::Resolved]);

                    Notification::make()
                        ->title('Állapot frissítve')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => $this->getRecord()->status !== FeedbackStatus::Resolved),
            Action::make('changeStatus')
                ->label('Állapot módosítása')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('Állapot')
                        ->options(FeedbackStatus::class)
                        ->required()
                        ->default(fn () => $this->getRecord()->status->value),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Állapot frissítve')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
