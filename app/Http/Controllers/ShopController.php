<?php

namespace App\Http\Controllers;

use App\Models\Shop\Brand;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Database\Eloquent\Collection;

class ShopController extends Controller
{
    /**
     * Display the shop home page with products and filters.
     */
    public function home()
    {
        $sort = request('sort', 'recommended');
        $perPage = in_array((int) request('per_page'), [24, 48, 96]) ? (int) request('per_page') : 24;

        $query = Product::with(['brand', 'categories', 'materialBasePrice', 'priceComponents.materialBasePrice', 'attributeValues.attribute', 'attributeValues.options'])
            ->where('is_visible', true);

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest('published_at'),
        };

        $products = $query->paginate($perPage)->withQueryString();

        $brands = Brand::orderBy('name')->get();

        $categories = $this->getCategories();
        $glossaryTerms = $this->getGlossaryTerms();

        $heroStats = [
            ['label' => 'Visszavásárlás', 'value' => '8 hónapos garancia'],
            ['label' => 'Tárolás', 'value' => '60 nap díjmentesen'],
            ['label' => 'Vásárlók', 'value' => '&gt; 10 000 elégedett ügyfél'],
            ['label' => 'Kiszállított m²', 'value' => '&gt; 120 000 m² hőszigetelés'],
        ];

        $productCount = $products->total();

        $homepageProducts = Product::homepage()
            ->with(['brand', 'categories'])
            ->take(6)
            ->get();

        $featuredProducts = Product::featured()
            ->with(['brand', 'categories'])
            ->take(6)
            ->get();

        $saleProducts = Product::onSale()
            ->with(['brand', 'categories'])
            ->take(8)
            ->get();

        $featuredCategories = Category::where('is_featured', true)
            ->where('is_visible', true)
            ->withCount(['products' => fn ($q) => $q->where('is_visible', true)])
            ->with(['children' => fn ($q) => $q->where('is_visible', true)
                ->withCount(['products' => fn ($q) => $q->where('is_visible', true)])])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->each(fn ($cat) => $cat->products_count += $cat->children->sum('products_count'));

        return view('shop.home', compact(
            'products',
            'brands',
            'categories',
            'glossaryTerms',
            'heroStats',
            'productCount',
            'homepageProducts',
            'featuredProducts',
            'saleProducts',
            'featuredCategories',
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
}
