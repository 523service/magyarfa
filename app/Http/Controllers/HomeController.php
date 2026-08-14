<?php

namespace App\Http\Controllers;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
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

        $categories = Category::withCount('products')
            ->having('products_count', '>', 0)
            ->take(8)
            ->get();

        return view('home', [
            'homepageProducts' => $homepageProducts,
            'featuredProducts' => $featuredProducts,
            'saleProducts' => $saleProducts,
            'categories' => $categories,
        ]);
    }
}
