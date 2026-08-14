<?php

namespace App\Filament\Clusters\Products\Resources\SystemTemplates;

use App\Filament\Clusters\Products\Resources\SystemTemplates\Pages\CreateSystemTemplate;
use App\Filament\Clusters\Products\Resources\SystemTemplates\Pages\EditSystemTemplate;
use App\Filament\Clusters\Products\Resources\SystemTemplates\Pages\ListSystemTemplates;
use App\Filament\Clusters\Products\Resources\SystemTemplates\RelationManagers\SystemTemplateItemsRelationManager;
use App\Filament\Clusters\Products\Resources\SystemTemplates\Schemas\SystemTemplateForm;
use App\Filament\Clusters\Products\Resources\SystemTemplates\Tables\SystemTemplatesTable;
use App\Models\Shop\SystemTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SystemTemplateResource extends Resource
{
    protected static ?string $model = SystemTemplate::class;

    protected static string | UnitEnum | null $navigationGroup = 'Products';

    protected static ?string $navigationLabel = 'Rendszer sablonok';

    protected static ?string $modelLabel = 'Rendszer sablon';

    protected static ?string $pluralModelLabel = 'Rendszer sablonok';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return SystemTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SystemTemplateItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemTemplates::route('/'),
            'create' => CreateSystemTemplate::route('/create'),
            'edit' => EditSystemTemplate::route('/{record}/edit'),
        ];
    }
}
