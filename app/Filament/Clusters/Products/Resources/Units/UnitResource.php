<?php

namespace App\Filament\Clusters\Products\Resources\Units;

use App\Filament\Clusters\Products\Resources\Units\Pages\CreateUnit;
use App\Filament\Clusters\Products\Resources\Units\Pages\EditUnit;
use App\Filament\Clusters\Products\Resources\Units\Pages\ListUnits;
use App\Filament\Clusters\Products\Resources\Units\Schemas\UnitForm;
use App\Filament\Clusters\Products\Resources\Units\Tables\UnitsTable;
use App\Models\Shop\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string | UnitEnum | null $navigationGroup = 'Products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
