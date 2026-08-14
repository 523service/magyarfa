<?php

namespace App\Filament\Clusters\Products\Resources\Products\Schemas;

use App\Filament\Clusters\Products\Resources\Brands\RelationManagers\ProductsRelationManager;
use App\Filament\Clusters\Products\Resources\Products\Support\AttributeFields;
use App\Models\Shop\Attribute;
use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\SystemTemplate;
use App\Models\Shop\Unit;
use App\Services\AI\ProductDescriptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set): void {
                                        if ($operation !== 'create') {
                                            return;
                                        }

                                        $set('slug', Str::slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                TextInput::make('manufacturer_website')
                                    ->label('Gyártó weboldala')
                                    ->placeholder('https://...')
                                    ->url()
                                    ->maxLength(255),

                                RichEditor::make('description')
                                    ->columnSpan('full')
                                    ->hintAction(
                                        Action::make('generateAiDescription')
                                            ->label('AI Leírás')
                                            ->icon('heroicon-o-sparkles')
                                            ->color('info')
                                            ->form([
                                                Select::make('provider')
                                                    ->label('AI Provider')
                                                    ->options([
                                                        'anthropic' => 'Anthropic (Claude)',
                                                        'openai' => 'OpenAI (GPT)',
                                                    ])
                                                    ->default(fn () => config('ai.default_provider'))
                                                    ->required(),
                                                Textarea::make('system_prompt')
                                                    ->label('Rendszer prompt')
                                                    ->rows(5)
                                                    ->default(fn () => config('ai.system_prompt'))
                                                    ->required(),
                                            ])
                                            ->modalHeading('AI Leírás generálása')
                                            ->modalSubmitActionLabel('Generálás')
                                            ->action(function (array $data, ProductDescriptionService $service, LivewireComponent $livewire): void {
                                                $record = $livewire->record ?? null;

                                                if ($record === null) {
                                                    Notification::make()
                                                        ->warning()
                                                        ->title('Mentsd el a terméket előbb, majd generálj leírást.')
                                                        ->send();

                                                    return;
                                                }

                                                try {
                                                    $result = $service->generate($record, $data['provider'], $data['system_prompt']);

                                                    $livewire->data['description'] = $result['description'];

                                                    $record->update([
                                                        'seo_description' => $result['seo_description'],
                                                        'ai_description_generated_at' => $result['ai_description_generated_at'],
                                                    ]);

                                                    Notification::make()
                                                        ->success()
                                                        ->title('AI leírás generálva')
                                                        ->send();
                                                } catch (Throwable $e) {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Generálás sikertelen')
                                                        ->body($e->getMessage())
                                                        ->send();
                                                }
                                            })
                                    ),
                            ])
                            ->columns(2),

                        Section::make('Images')
                            ->schema([
                                Toggle::make('use_shared_image')
                                    ->label('Use shared image from another product')
                                    ->live()
                                    ->afterStateHydrated(function ($state, $record, Set $set) {
                                        $set('use_shared_image', $record?->shared_media_id !== null);
                                    })
                                    ->dehydrated(false),

                                Select::make('shared_media_id')
                                    ->label('Shared Image')
                                    ->options(
                                        Media::query()
                                            ->where('collection_name', 'product-images')
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->visible(fn (Get $get) => $get('use_shared_image'))
                                    ->helperText('This image will be used instead of the product\'s own uploaded image.'),

                                SpatieMediaLibraryFileUpload::make('media')
                                    ->collection('product-images')
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->reorderable()
                                    ->hiddenLabel()
                                    ->visible(fn (Get $get) => ! $get('use_shared_image')),
                            ])
                            ->collapsible(),

                        Section::make('Árazás')
                            ->schema([
                                Select::make('pricing_mode')
                                    ->label('Árazási mód')
                                    ->options([
                                        'manual' => 'Manuális',
                                        'formula' => 'Képlet (anyag × vastagság)',
                                        'system_template' => 'Rendszer sablon',
                                    ])
                                    ->required()
                                    ->default('manual')
                                    ->live()
                                    ->columnSpanFull(),

                                TextInput::make('price')
                                    ->label('Eladási ár (br.)')
                                    ->numeric()
                                    ->rules(['regex:/^\d{1,6}(\.\d{0,2})?$/'])
                                    ->helperText('Manuális módban ez az érvényes ár.')
                                    ->required()
                                    ->visible(fn (Get $get) => $get('pricing_mode') === 'manual'),

                                Select::make('formula_type')
                                    ->label('Képlet típusa')
                                    ->options([
                                        'board_by_thickness_cm' => 'Lap ára: egységár × vastagság (cm)',
                                        'fixed_unit_price' => 'Fix egységár',
                                    ])
                                    ->required()
                                    ->visible(fn (Get $get) => $get('pricing_mode') === 'formula')
                                    ->columnSpanFull(),

                                Select::make('material_price_id')
                                    ->label('Anyag alapár')
                                    ->options(
                                        MaterialBasePrice::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($mbp) => [
                                                $mbp->id => "{$mbp->name} ({$mbp->price_per_unit} Ft/{$mbp->unit_label})",
                                            ])
                                    )
                                    ->searchable()
                                    ->required()
                                    ->visible(fn (Get $get) => $get('pricing_mode') === 'formula')
                                    ->columnSpanFull(),

                                Select::make('system_template_id')
                                    ->label('Rendszer sablon')
                                    ->options(
                                        SystemTemplate::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->visible(fn (Get $get) => $get('pricing_mode') === 'system_template')
                                    ->columnSpanFull(),

                                TextInput::make('thickness_cm')
                                    ->label('Vastagság (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.5)
                                    ->suffix('cm')
                                    ->helperText('Szükséges képlet és rendszer sablon módhoz.')
                                    ->visible(fn (Get $get) => in_array($get('pricing_mode'), ['formula', 'system_template']))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Egyéb ártámogatás')
                            ->schema([
                                TextInput::make('old_price')
                                    ->label('Konkurencia ára')
                                    ->numeric()
                                    ->rules(['regex:/^\d{1,6}(\.\d{0,2})?$/']),

                                TextInput::make('cost')
                                    ->label('Beszerzési ár')
                                    ->helperText('Az oldalon nem jelenik meg.')
                                    ->numeric()
                                    ->rules(['regex:/^\d{1,6}(\.\d{0,2})?$/']),
                            ])
                            ->columns(2)
                            ->collapsible(),
                        Section::make('Inventory')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique(Product::class, 'sku', ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('barcode')
                                    ->label('Barcode (ISBN, UPC, GTIN, etc.)')
                                    ->unique(Product::class, 'barcode', ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('qty')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->rules(['integer', 'min:0'])
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(2),

                        Section::make('Shipping')
                            ->schema([

                                Checkbox::make('requires_shipping')
                                    ->label('This product will be shipped'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('Visibility')
                                    ->helperText('This product will be hidden from all sales channels.')
                                    ->default(true),

                                DatePicker::make('published_at')
                                    ->label('Publishing date')
                                    ->default(now())
                                    ->required(),

                                TextInput::make('position')
                                    ->label('Sorrend')
                                    ->numeric()
                                    ->default(0)
                                    ->rules(['integer', 'min:0']),
                            ]),

                        Section::make('Megjelenítés')
                            ->schema([
                                Toggle::make('is_homepage')
                                    ->label('Főoldalon szerepel')
                                    ->helperText('A termék megjelenik a főoldal showcase szekciójában.')
                                    ->default(false),

                                Toggle::make('featured')
                                    ->label('Kiemelt termék')
                                    ->helperText('A termék megjelenik a kiemelt termékek szekciójában.')
                                    ->default(false),

                                Toggle::make('is_on_sale')
                                    ->label('Akciós termék')
                                    ->helperText('A termék megjelenik az akciók szekciójában.')
                                    ->default(false),
                            ]),

                        Section::make('Associations')
                            ->schema([
                                Select::make('shop_brand_id')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->hiddenOn(ProductsRelationManager::class),

                                Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->required(),

                                Select::make('units')
                                    ->label('Units')
                                    ->relationship('units', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->live(),

                                Select::make('primary_unit_id')
                                    ->label('Primary Unit')
                                    ->options(fn () => Unit::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select the primary unit for this product.'),
                            ]),

                        Section::make('Attributes')
                            ->schema(fn (): array => AttributeFields::buildFields())
                            ->hidden(fn () => Attribute::where('is_visible', true)->count() === 0),

                        Section::make('Mértékegység & Rendelési beállítások')
                            ->schema([
                                Fieldset::make()
                                    ->relationship('unitConfig')
                                    ->schema([
                                        Select::make('base_unit_id')
                                            ->label('Alap egység')
                                            ->options(
                                                Unit::orderByDesc('is_base_unit')
                                                    ->orderBy('sort_order')
                                                    ->pluck('label', 'id')
                                                    ->filter()
                                            )
                                            ->searchable()
                                            ->required()
                                            ->columnSpanFull(),

                                        TextInput::make('price_per_base_unit')
                                            ->label('Egységár (Ft)')
                                            ->helperText('Üresen hagyva a termék árát használja.')
                                            ->numeric()
                                            ->suffix('Ft')
                                            ->rules(['nullable', 'numeric', 'min:0']),

                                        TextInput::make('min_order_qty')
                                            ->label('Min. mennyiség')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->rules(['numeric', 'min:0']),

                                        TextInput::make('order_step')
                                            ->label('Lépésköz')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->rules(['numeric', 'min:0']),

                                        Select::make('secondary_unit_id')
                                            ->label('Másodlagos egység')
                                            ->helperText('Pl. bála, zsák, csomag')
                                            ->options(
                                                Unit::where('is_base_unit', false)
                                                    ->orderBy('sort_order')
                                                    ->pluck('label', 'id')
                                                    ->filter()
                                            )
                                            ->searchable()
                                            ->nullable()
                                            ->live()
                                            ->columnSpanFull(),

                                        TextInput::make('secondary_unit_qty')
                                            ->label('1 másodlagos = X alap egység')
                                            ->helperText('Pl. 1 bála = 25 m²')
                                            ->numeric()
                                            ->rules(['nullable', 'numeric', 'min:0'])
                                            ->visible(fn (Get $get): bool => (bool) $get('secondary_unit_id'))
                                            ->columnSpanFull(),

                                        TextInput::make('notes')
                                            ->label('Megjegyzés')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
