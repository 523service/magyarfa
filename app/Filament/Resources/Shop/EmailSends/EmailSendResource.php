<?php

namespace App\Filament\Resources\Shop\EmailSends;

use App\Filament\Resources\Shop\EmailSends\Pages\ListEmailSends;
use App\Filament\Resources\Shop\EmailSends\Pages\ViewEmailSend;
use App\Filament\Resources\Shop\EmailSends\Schemas\EmailSendForm;
use App\Filament\Resources\Shop\EmailSends\Tables\EmailSendsTable;
use App\Models\EmailSend;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class EmailSendResource extends Resource
{
    protected static ?string $model = EmailSend::class;

    protected static ?string $slug = 'shop/email-sends';

    protected static ?string $recordTitleAttribute = 'recipient_email';

    protected static string | UnitEnum | null $navigationGroup = 'Shop';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email követés';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return EmailSendForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailSendsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailSends::route('/'),
            'view' => ViewEmailSend::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
