<?php

namespace App\Http\Controllers;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(): View
    {
        return view('shop.search', [
            'query' => request()->query('q', ''),
        ]);
    }

    /**
     * Return 3 latest + 3 featured products for the default (empty query) search state.
     */
    public function defaults(): JsonResponse
    {
        $format = fn (Product $p): array => [
            'title' => $p->name,
            'url' => route('product.show', $p->slug),
            'thumb' => $p->getMainImageUrl('thumb') ?: null,
            'meta' => $p->brand?->name,
        ];

        $latest = Product::where('is_visible', true)
            ->with(['brand', 'media', 'sharedMedia'])
            ->latest()
            ->limit(3)
            ->get()
            ->map($format);

        $popular = Product::where('is_visible', true)
            ->where('featured', true)
            ->with(['brand', 'media', 'sharedMedia'])
            ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->latest()
            ->limit(3)
            ->get();

        // If fewer than 3 featured products exist, fill with additional recent ones.
        if ($popular->count() < 3) {
            $exclude = $popular->pluck('id')
                ->merge(Product::where('is_visible', true)->latest()->limit(3)->pluck('id'));

            $fill = Product::where('is_visible', true)
                ->whereNotIn('id', $exclude)
                ->with(['brand', 'media', 'sharedMedia'])
                ->latest()
                ->limit(3 - $popular->count())
                ->get();

            $popular = $popular->concat($fill);
        }

        return response()->json([
            'latest' => $latest,
            'popular' => $popular->map($format),
        ]);
    }

    /**
     * Autocomplete via Algolia Scout — returns matching products and categories.
     */
    public function autocomplete(): JsonResponse
    {
        $query = trim((string) request()->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        try {
            $products = Product::search($query)->get()->take(5);
            $categories = Category::search($query)->get()->take(3);

            $products->load(['brand', 'media', 'sharedMedia']);
            $categories->loadCount('products');

            return response()->json([
                'products' => $products->map(fn (Product $p) => [
                    'title' => $p->name,
                    'url' => route('product.show', $p->slug),
                    'thumb' => $p->getMainImageUrl('thumb') ?: null,
                    'meta' => $p->brand?->name,
                    'price' => number_format((float) $p->price, 0, ',', ' ') . ' Ft',
                ]),
                'categories' => $categories->map(fn (Category $c) => [
                    'title' => $c->name,
                    'url' => route('category.show', $c->slug),
                    'thumb' => $c->getFirstMediaUrl() ?: null,
                    'meta' => $c->products_count . ' termék',
                ]),
            ]);
        } catch (Exception) {
            return response()->json(['products' => [], 'categories' => []]);
        }
    }
}
