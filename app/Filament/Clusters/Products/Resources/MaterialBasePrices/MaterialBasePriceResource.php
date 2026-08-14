<?php

namespace App\Filament\Clusters\Products\Resources\MaterialBasePrices;

use App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages\CreateMaterialBasePrice;
use App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages\EditMaterialBasePrice;
use App\Filament\Clusters\Products\Resources\MaterialBasePrices\Pages\ListMaterialBasePrices;
use App\Filament\Clusters\Products\Resources\MaterialBasePrices\Schemas\MaterialBasePriceForm;
use App\Filament\Clusters\Products\Resources\MaterialBasePrices\Tables\MaterialBasePricesTable;
use App\Models\Shop\MaterialBasePrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MaterialBasePriceResource extends Resource
{
    protected static ?string $model = MaterialBasePrice::class;

    protected static string | UnitEnum | null $navigationGroup = 'Products';

    protected static ?string $navigationLabel = 'Alapárak';

    protected static ?string $modelLabel = 'Alapár';

    protected static ?string $pluralModelLabel = 'Alapárak';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return MaterialBasePriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialBasePricesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialBasePrices::route('/'),
            'create' => CreateMaterialBasePrice::route('/create'),
            'edit' => EditMaterialBasePrice::route('/{record}/edit'),
        ];
    }
}
