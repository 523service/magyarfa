<?php

namespace App\Filament\Resources\Feedbacks;

use App\Filament\Resources\Feedbacks\Pages\ListFeedbacks;
use App\Filament\Resources\Feedbacks\Pages\ViewFeedback;
use App\Filament\Resources\Feedbacks\Schemas\FeedbackForm;
use App\Filament\Resources\Feedbacks\Schemas\FeedbackInfolist;
use App\Filament\Resources\Feedbacks\Tables\FeedbacksTable;
use App\Models\Feedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static ?string $slug = 'feedbacks';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | UnitEnum | null $navigationGroup = 'Általános';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Visszajelzések';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return FeedbackForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedbackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbacksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedbacks::route('/'),
            'view' => ViewFeedback::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'new')->count() ?: null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Feedback $record */
        return [
            'Email' => $record->email,
            'Oldal' => $record->url,
        ];
    }
}
