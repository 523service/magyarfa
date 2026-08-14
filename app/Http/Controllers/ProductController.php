<?php

namespace App\Http\Controllers;

use App\Models\Shop\Product;
use App\Models\Shop\ProductAttributeValue;
use App\Services\PriceResolverService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display the specified product.
     */
    public function show(string $slug, PriceResolverService $priceResolver): View
    {
        $isAdmin = Auth::check() && Auth::user()->is_admin;

        $eagerLoads = [
            'brand',
            'categories',
            'media',
            'attributeValues.attribute',
            'attributeValues.options',
            'materialBasePrice',
            'priceComponents.materialBasePrice',
        ];

        if ($isAdmin) {
            $eagerLoads[] = 'competitorLinks';
        }

        $product = Product::with($eagerLoads)
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->firstOrFail();

        $resolvedPrice = $priceResolver->resolve($product);
        $attributes = $this->getProductAttributes($product);
        $specifications = $this->getProductSpecifications($product);
        $relatedProducts = $this->getRelatedProducts($product);
        $competitorLinks = $isAdmin
            ? $product->competitorLinks->where('scrape_status', 'success')
            : collect();

        return view('shop.product', compact(
            'product',
            'resolvedPrice',
            'attributes',
            'specifications',
            'relatedProducts',
            'competitorLinks'
        ));
    }

    /**
     * Get product attributes for display.
     *
     * @return array<int, array<string, string>>
     */
    private function getProductAttributes(Product $product): array
    {
        $attributes = [];

        if ($product->sku) {
            $attributes[] = ['name' => 'Cikkszám', 'value' => $product->sku];
        }

        if ($product->brand) {
            $attributes[] = ['name' => 'Gyártó', 'value' => $product->brand->name];
        }

        // Add EAV attributes (only visible ones)
        foreach ($product->attributeValues as $attributeValue) {
            if (! $attributeValue->attribute->is_visible) {
                continue;
            }

            $value = $this->getAttributeValueDisplay($attributeValue);
            if ($value !== null) {
                $attributes[] = [
                    'name' => $attributeValue->attribute->name,
                    'value' => $value,
                ];
            }
        }

        return $attributes;
    }

    /**
     * Get the display value for an attribute based on its type.
     */
    private function getAttributeValueDisplay(ProductAttributeValue $attributeValue): ?string
    {
        $attribute = $attributeValue->attribute;

        // Handle select/multi-select types (with options)
        if ($attributeValue->options->isNotEmpty()) {
            $optionValues = $attributeValue->options->pluck('value')->toArray();
            $value = implode(', ', $optionValues);
            if ($attribute->unit) {
                $value .= ' ' . $attribute->unit;
            }

            return $value;
        }

        // Handle different value types
        if ($attributeValue->text_value !== null) {
            $value = $attributeValue->text_value;
            if ($attribute->unit) {
                $value .= ' ' . $attribute->unit;
            }

            return $value;
        }

        if ($attributeValue->number_value !== null) {
            $value = (string) $attributeValue->number_value;
            if ($attribute->unit) {
                $value .= ' ' . $attribute->unit;
            }

            return $value;
        }

        if ($attributeValue->boolean_value !== null) {
            return $attributeValue->boolean_value ? 'Igen' : 'Nem';
        }

        return null;
    }

    /**
     * Get product specifications for the specifications tab.
     *
     * @return array<int, array<string, string>>
     */
    private function getProductSpecifications(Product $product): array
    {
        $specs = [];

        if ($product->sku) {
            $specs[] = ['name' => 'Cikkszám', 'value' => $product->sku];
        }

        if ($product->brand) {
            $specs[] = ['name' => 'Gyártó', 'value' => $product->brand->name];
        }

        if ($product->barcode) {
            $specs[] = ['name' => 'Vonalkód', 'value' => $product->barcode];
        }

        if ($product->requires_shipping) {
            $specs[] = ['name' => 'Szállítás szükséges', 'value' => 'Igen'];
        }

        // Add EAV attributes to specifications (only visible ones)
        foreach ($product->attributeValues as $attributeValue) {
            if (! $attributeValue->attribute->is_visible) {
                continue;
            }

            $value = $this->getAttributeValueDisplay($attributeValue);
            if ($value !== null) {
                $specs[] = [
                    'name' => $attributeValue->attribute->name,
                    'value' => $value,
                ];
            }
        }

        return $specs;
    }

    /**
     * Get related products based on shared categories.
     *
     * @return Collection<int, Product>
     */
    private function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        $categoryIds = $product->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return Product::with(['brand', 'media'])
                ->where('is_visible', true)
                ->where('id', '!=', $product->id)
                ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
                ->orderBy('position')
                ->latest('published_at')
                ->limit($limit)
                ->get();
        }

        return Product::with(['brand', 'media'])
            ->where('is_visible', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('shop_categories.id', $categoryIds);
            })
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}
