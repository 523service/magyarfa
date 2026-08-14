<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Livewire\Shop\Cart\CartPage;
use App\Livewire\Shop\Checkout\CheckoutPage;
use App\Livewire\Shop\Checkout\OrderConfirmation;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('welcome');

// Create alias for 'home' route (used in templates)
Route::get('/', [ShopController::class, 'home'])->name('home');
// Route::get('/home', [HomeController::class, 'index'])->name('home');

// Category page
Route::get('/kategoria/{slug}', [CategoryController::class, 'show'])->name('category.show');

// Product page
Route::get('/termek/{slug}', [ProductController::class, 'show'])->name('product.show');

// Search — sub-routes must come before the index to avoid slug conflicts
Route::get('/kereses/defaults', [SearchController::class, 'defaults'])->name('search.defaults');
Route::get('/kereses/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
Route::get('/kereses', [SearchController::class, 'index'])->name('search.index');

// Cart page
Route::get('/kosar', CartPage::class)->name('cart.index');

// Checkout
Route::get('/rendeles', CheckoutPage::class)->name('checkout.index');
Route::get('/rendeles/megerosites/{number}', OrderConfirmation::class)->name('order.confirmation');

// Email Tracking
Route::get('/email/pixel/{token}.png', [EmailTrackingController::class, 'pixel'])->name('email.pixel');
Route::get('/email/click/{token}', [EmailTrackingController::class, 'click'])->name('email.click');

// Shop routes
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'home'])->name('home');
});

Route::get('/dashboard', function () {
    return redirect()->route('profile.edit');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    Route::get('/contact', [ProfileController::class, 'contact'])->name('contact');
    Route::get('/addresses', [ProfileController::class, 'addresses'])->name('addresses');
    Route::get('/password', [ProfileController::class, 'password'])->name('password');
    Route::get('/delete', [ProfileController::class, 'deleteAccount'])->name('delete');
});

require __DIR__ . '/auth.php';
