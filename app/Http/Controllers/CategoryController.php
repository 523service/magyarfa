<?php

namespace App\Http\Controllers;

use App\Models\Shop\Attribute;
use App\Models\Shop\Brand;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\ProductAttributeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a category page with its products.
     */
    public function show(string $slug): View
    {
        $category = Category::with(['parent', 'children' => function ($query) {
            $query->where('is_visible', true)
                ->orderBy('position')
                ->orderBy('name');
        }])
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->firstOrFail();

        // Current category + visible children category IDs (children already eager-loaded)
        $categoryIds = $category->children->pluck('id')->push($category->id);

        // Get filter parameters
        $selectedBrands = request()->get('brands', []);
        $selectedAttributes = request()->get('attributes', []);

        // Build products query with filters (includes products from subcategories)
        $productsQuery = Product::query()
            ->whereHas('categories', fn ($q) => $q->whereIn('shop_categories.id', $categoryIds))
            ->with(['brand', 'categories', 'attributeValues.attribute', 'attributeValues.options', 'materialBasePrice', 'priceComponents.materialBasePrice'])
            ->where('is_visible', true);

        // Apply brand filter
        if (! empty($selectedBrands)) {
            $productsQuery->whereIn('shop_brand_id', $selectedBrands);
        }

        // Apply attribute filters
        if (! empty($selectedAttributes)) {
            foreach ($selectedAttributes as $attributeId => $values) {
                if (! empty($values)) {
                    // Get the attribute to determine its type
                    $attribute = Attribute::find($attributeId);
                    if (! $attribute) {
                        continue;
                    }

                    $productsQuery->whereHas('attributeValues', function ($query) use ($attributeId, $values, $attribute) {
                        $query->where('shop_attribute_id', $attributeId);

                        // Check if this attribute uses options or direct values
                        $hasOptions = $attribute->options()->exists();

                        if ($hasOptions) {
                            // Filter by options (select-type attributes)
                            $query->whereHas('options', function ($q) use ($values) {
                                $q->whereIn('shop_attribute_options.id', $values);
                            });
                        } else {
                            // Filter by direct values (number/text/boolean attributes)
                            $valueColumn = match ($attribute->type) {
                                'text' => 'text_value',
                                'number' => 'number_value',
                                'boolean' => 'boolean_value',
                                default => 'text_value',
                            };

                            $query->whereIn($valueColumn, $values);
                        }
                    });
                }
            }
        }

        $sort = request('sort', 'recommended');
        $perPage = in_array((int) request('per_page'), [24, 48, 96]) ? (int) request('per_page') : 24;

        match ($sort) {
            'price_asc' => $productsQuery->orderBy('price', 'asc'),
            'price_desc' => $productsQuery->orderBy('price', 'desc'),
            default => $productsQuery->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')->orderBy('position')->latest('published_at'),
        };

        $products = $productsQuery->paginate($perPage)->withQueryString();

        // Get available filters for this category
        $availableFilters = $this->getAvailableFilters($category, $categoryIds);

        $categories = $this->getCategories();
        $glossaryTerms = $this->getGlossaryTerms();

        $productCount = $products->total();

        return view('shop.category', compact(
            'category',
            'products',
            'categories',
            'glossaryTerms',
            'productCount',
            'availableFilters',
            'selectedBrands',
            'selectedAttributes',
        ));
    }

    /**
     * Get parent categories with visible children for sidebar.
     *
     * @return Collection<int, Category>
     */
    private function getCategories(): Collection
    {
        return Category::with(['children' => function ($query) {
            $query->where('is_visible', true)
                ->orderBy('position')
                ->orderBy('name');
        }])
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get glossary terms for sidebar.
     *
     * @return array<int, array<string, string>>
     */
    private function getGlossaryTerms(): array
    {
        return [
            ['name' => 'Hungarocell', 'slug' => 'hungarocell'],
            ['name' => 'Polisztirol', 'slug' => 'polisztirol'],
            ['name' => 'Homlokzati hőszigetelés', 'slug' => 'homlokzati-hoszigeteles'],
            ['name' => 'Grafitos hőszigetelés', 'slug' => 'grafitos-hoszigeteles'],
            ['name' => 'Lépésálló szigetelés', 'slug' => 'lepesallo-szigeteles'],
            ['name' => 'Lábazati hőszigetelés', 'slug' => 'labazati-hoszigeteles'],
            ['name' => 'Ásványgyapot', 'slug' => 'asvanygyapot'],
            ['name' => 'Kőzetgyapot', 'slug' => 'kozetgyapot'],
            ['name' => 'Üveggyapot', 'slug' => 'uveggyapot'],
        ];
    }

    /**
     * Get available filters (brands and attributes) for products in this category.
     *
     * @return array{brands: \Illuminate\Support\Collection, attributes: \Illuminate\Support\Collection}
     */
    private function getAvailableFilters(Category $category, \Illuminate\Support\Collection $categoryIds): array
    {
        // Get all product IDs in this category and its subcategories
        $productIds = Product::query()
            ->whereHas('categories', fn ($q) => $q->whereIn('shop_categories.id', $categoryIds))
            ->where('is_visible', true)
            ->pluck('id');

        if ($productIds->isEmpty()) {
            return ['brands' => collect(), 'attributes' => collect()];
        }

        // Get brands with product counts
        $brands = Brand::whereHas('products', function ($query) use ($productIds) {
            $query->whereIn('shop_products.id', $productIds);
        })
            ->withCount(['products' => function ($query) use ($productIds) {
                $query->whereIn('shop_products.id', $productIds);
            }])
            ->orderBy('name')
            ->get();

        // Get filterable attributes used by products in this category
        $attributes = Attribute::whereHas('productValues', function ($query) use ($productIds) {
            $query->whereIn('shop_product_id', $productIds);
        })
            ->where('is_visible', true)
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // For each attribute, get either options or distinct values
        $attributes->each(function ($attribute) use ($productIds) {
            // First, try to load options (for select-type attributes)
            $attribute->load(['options' => function ($query) use ($productIds) {
                $query->whereHas('productValues', function ($q) use ($productIds) {
                    $q->whereIn('shop_product_id', $productIds);
                })
                    ->withCount(['productValues' => function ($q) use ($productIds) {
                        $q->whereIn('shop_product_id', $productIds);
                    }])
                    ->having('product_values_count', '>', 0)
                    ->orderBy('sort_order')
                    ->orderBy('value');
            }]);

            // If no options exist, get distinct values from product_attribute_values
            if ($attribute->options->isEmpty()) {
                $attribute->distinctValues = $this->getDistinctAttributeValues($attribute, $productIds);
            }
        });

        // Filter out attributes that have neither options nor distinct values
        $attributes = $attributes->filter(function ($attr) {
            return $attr->options->isNotEmpty() || (isset($attr->distinctValues) && $attr->distinctValues->isNotEmpty());
        });

        return [
            'brands' => $brands,
            'attributes' => $attributes,
        ];
    }

    /**
     * Get distinct values for an attribute from products.
     *
     * @param  \Illuminate\Support\Collection<int>  $productIds
     * @return \Illuminate\Support\Collection<int, object{value: string, count: int}>
     */
    private function getDistinctAttributeValues(Attribute $attribute, $productIds): \Illuminate\Support\Collection
    {
        $valueColumn = match ($attribute->type) {
            'text' => 'text_value',
            'number' => 'number_value',
            'boolean' => 'boolean_value',
            default => 'text_value',
        };

        $values = ProductAttributeValue::where('shop_attribute_id', $attribute->id)
            ->whereIn('shop_product_id', $productIds)
            ->whereNotNull($valueColumn)
            ->select($valueColumn)
            ->selectRaw('COUNT(*) as count')
            ->groupBy($valueColumn)
            ->orderBy($valueColumn)
            ->get();

        // Format the values to match the options structure
        return $values->map(function ($item) use ($valueColumn, $attribute) {
            $value = $item->{$valueColumn};

            // Format the display value
            $displayValue = $attribute->type === 'boolean'
                ? ($value ? 'Igen' : 'Nem')
                : $value;

            // Add unit if exists
            if ($attribute->unit && in_array($attribute->type, ['text', 'number'])) {
                $displayValue .= ' ' . $attribute->unit;
            }

            return (object) [
                'id' => $value, // Use the actual value as ID for filtering
                'value' => $displayValue,
                'product_values_count' => $item->count,
                'raw_value' => $value, // Store raw value for filtering
            ];
        });
    }
}
