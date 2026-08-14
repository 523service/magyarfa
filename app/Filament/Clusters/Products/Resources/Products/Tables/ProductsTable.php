<?php

namespace App\Filament\Clusters\Products\Resources\Products\Tables;

use App\Actions\Shop\CloneProductAction;
use App\Filament\Clusters\Products\Resources\Products\ProductResource;
use App\Filament\Clusters\Products\Resources\Products\Support\AttributeFields;
use App\Jobs\GenerateProductDescriptionJob;
use App\Models\Shop\Category;
use App\Models\Shop\Unit;
use App\Services\AI\ProductDescriptionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Livewire\Component;
use Throwable;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                ImageColumn::make('main_image')
                    ->label('Image')
                    ->getStateUsing(fn ($record) => $record->getMainImageUrl('thumb'))
                    ->circular(false)
                    ->width(60),
                /*
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('product-images')
                    ->conversion('thumb'),
                */

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_homepage')
                    ->label('Főoldal')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                IconColumn::make('featured')
                    ->label('Kiemelt')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                IconColumn::make('is_on_sale')
                    ->label('Akciós')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('price')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('qty')
                    ->label('Quantity')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('display_unit')
                    ->label('Unit')
                    ->toggleable(),

                TextColumn::make('position')
                    ->label('Sorrend')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publishing date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->reorderable('position')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name'),
                        TextConstraint::make('slug'),
                        TextConstraint::make('sku')
                            ->label('SKU (Stock Keeping Unit)'),
                        TextConstraint::make('barcode')
                            ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                        TextConstraint::make('description'),
                        NumberConstraint::make('old_price')
                            ->label('Compare at price')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('price')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('cost')
                            ->label('Cost per item')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('qty')
                            ->label('Quantity'),
                        BooleanConstraint::make('is_visible')
                            ->label('Visibility'),
                        BooleanConstraint::make('is_homepage')
                            ->label('Főoldalon szerepel'),
                        BooleanConstraint::make('featured')
                            ->label('Kiemelt termék'),
                        BooleanConstraint::make('is_on_sale')
                            ->label('Akciós termék'),
                        BooleanConstraint::make('backorder'),
                        BooleanConstraint::make('requires_shipping')
                            ->icon('heroicon-m-truck'),
                        DateConstraint::make('published_at')
                            ->label('Publishing date'),
                        RelationshipConstraint::make('categories')
                            ->label('Kategória')
                            ->icon('heroicon-m-tag')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->native(false),
                            ),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->recordActions([
                EditAction::make(),
                Action::make('generateDescription')
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
                    ->requiresConfirmation(fn ($record) => filled($record->description))
                    ->modalHeading('Meglévő leírás felülírása?')
                    ->modalDescription(fn ($record) => filled($record->description)
                        ? "\"{$record->name}\" terméknek már van leírása. Biztosan felülírja az AI által generált szöveggel?"
                        : null)
                    ->modalSubmitActionLabel('Generálás')
                    ->action(function ($record, array $data, ProductDescriptionService $service): void {
                        try {
                            $result = $service->generate($record, $data['provider'], $data['system_prompt']);
                            $record->update($result);

                            Notification::make()
                                ->title('AI leírás sikeresen generálva')
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Generálás sikertelen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('clone')
                    ->label('Klónozás')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Termék klónozása')
                    ->modalDescription(fn ($record) => "\"{$record->name}\" klónozása egy új, rejtett másolatot hoz létre azonos kategóriákkal, egységekkel és attribútumokkal.")
                    ->modalSubmitActionLabel('Klónozás')
                    ->action(function ($record, CloneProductAction $cloneAction, Component $livewire): void {
                        $clone = $cloneAction->handle($record);
                        $livewire->redirect(ProductResource::getUrl('edit', ['record' => $clone]));
                    }),
                Action::make('open')
                    ->label('Link')
                    ->color('success')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => route('product.show', $record->slug))
                    ->openUrlInNewTab(),
            ])
            ->groupedBulkActions([
                BulkAction::make('generateDescriptions')
                    ->label('AI Leírás generálás')
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
                        Toggle::make('overwrite')
                            ->label('Meglévő leírások felülírása')
                            ->helperText('Ha kikapcsolt, azokat a termékeket kihagyja, amelyeknek már van leírása.')
                            ->default(false),
                    ])
                    ->action(function (Collection $records, array $data, ProductDescriptionService $service): void {
                        $useQueue = config('ai.bulk_mode') === 'queue';
                        $count = 0;

                        foreach ($records as $product) {
                            if (filled($product->description) && ! $data['overwrite']) {
                                continue;
                            }

                            if ($useQueue) {
                                GenerateProductDescriptionJob::dispatch($product, $data['provider'], $data['system_prompt']);
                            } else {
                                try {
                                    $result = $service->generate($product, $data['provider'], $data['system_prompt']);
                                    $product->update($result);
                                } catch (Throwable) {
                                    continue;
                                }
                            }

                            $count++;
                        }

                        $label = $useQueue ? 'sorba rakva (háttérben fut)' : 'generálva';

                        Notification::make()
                            ->title("{$count} termék leírása {$label}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                DeleteBulkAction::make()
                    ->action(function (): void {
                        Notification::make()
                            ->title('Tömeges törlés action')
                            ->warning()
                            ->send();
                    }),

                BulkAction::make('assignCategories')
                    ->label('Kategória hozzárendelése')
                    ->icon('heroicon-o-tag')
                    ->form([
                        Select::make('categories')
                            ->label('Kategóriák')
                            ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->required(),
                        Radio::make('mode')
                            ->label('Hozzárendelés módja')
                            ->options([
                                'sync' => 'Felülír (csak ezek maradnak)',
                                'add' => 'Hozzáad (meglévők megmaradnak)',
                            ])
                            ->default('add')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $product) {
                            if ($data['mode'] === 'sync') {
                                $product->categories()->sync($data['categories']);
                            } else {
                                $product->categories()->syncWithoutDetaching($data['categories']);
                            }
                        }

                        Notification::make()
                            ->title('Kategóriák hozzárendelve')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('assignUnits')
                    ->label('Unit hozzárendelése')
                    ->icon('heroicon-o-scale')
                    ->form([
                        Select::make('units')
                            ->label('Mértékegységek')
                            ->options(fn () => Unit::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->live()
                            ->required(),
                        Select::make('primary_unit_id')
                            ->label('Elsődleges mértékegység')
                            ->options(fn (Get $get): array => Unit::whereIn('id', $get('units') ?? [])->pluck('name', 'id')->toArray())
                            ->required(),
                        Radio::make('mode')
                            ->label('Hozzárendelés módja')
                            ->options([
                                'sync' => 'Felülír (csak ezek maradnak)',
                                'add' => 'Hozzáad (meglévők megmaradnak)',
                            ])
                            ->default('add')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $pivotData = collect($data['units'])->mapWithKeys(fn ($id) => [
                            $id => ['is_primary' => $id == $data['primary_unit_id']],
                        ])->all();

                        foreach ($records as $product) {
                            if ($data['mode'] === 'sync') {
                                $product->units()->sync($pivotData);
                            } else {
                                foreach ($pivotData as $unitId => $pivot) {
                                    $product->units()->syncWithoutDetaching([$unitId => $pivot]);
                                }
                            }
                        }

                        Notification::make()
                            ->title('Unitok hozzárendelve')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('assignAttributes')
                    ->label('Attribútum értékek beállítása')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form(fn (): array => AttributeFields::buildFields())
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $product) {
                            AttributeFields::saveAttributeValues($product, $data);
                        }

                        Notification::make()
                            ->title('Attribútum értékek beállítva')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
