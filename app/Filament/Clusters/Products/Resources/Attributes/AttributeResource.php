<?php

namespace App\Filament\Clusters\Products\Resources\Attributes;

use App\Filament\Clusters\Products\Resources\Attributes\Pages\CreateAttribute;
use App\Filament\Clusters\Products\Resources\Attributes\Pages\EditAttribute;
use App\Filament\Clusters\Products\Resources\Attributes\Pages\ListAttributes;
use App\Filament\Clusters\Products\Resources\Attributes\RelationManagers\OptionsRelationManager;
use App\Filament\Clusters\Products\Resources\Attributes\Schemas\AttributeForm;
use App\Filament\Clusters\Products\Resources\Attributes\Tables\AttributesTable;
use App\Models\Shop\Attribute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Products';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
