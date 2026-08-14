# Shopping Cart Implementation Plan
> MagyarSzigeteles.hu - Kosar funkcionalitas

## 1. Architektura attekintes

### 1.1 Komponens struktura

```
app/Livewire/Shop/Cart/
    CartCounter.php          # Header badge (termekszam)
    CartModal.php            # Kosarba teves felugro ablak
    CartPage.php             # Teljes kosar oldal
    AddToCartButton.php      # Kosarba gomb komponens

app/Services/
    CartService.php          # Kosar uzleti logika

resources/views/livewire/shop/cart/
    cart-counter.blade.php
    cart-modal.blade.php
    cart-page.blade.php
    add-to-cart-button.blade.php
```

### 1.2 Uj route

```php
// routes/web.php
Route::get('/kosar', CartPage::class)->name('cart.index');
```

---

## 2. Kosar elem struktura (Darryldecode Cart)

### 2.1 Egyedi sor ID strategia

A product + unit + attributumok kombinacioja alapjan generalunk egyedi ID-t:

```php
// Pelda ID-k:
// Egyszeru: "123-5" (product 123, unit 5)
// Attributumokkal: "123-5-a1b2c3d4" (product 123, unit 5, attributum hash)

protected function generateRowId(int $productId, int $unitId, array $attributes = []): string
{
    $attributeHash = empty($attributes) ? '' : '-' . substr(md5(serialize($attributes)), 0, 8);
    return sprintf('%d-%d%s', $productId, $unitId, $attributeHash);
}
```

### 2.2 Kosar elem adatstruktura

```php
Cart::add([
    'id' => '123-5',                    // Kompozit sor ID
    'name' => 'Hoszigetelo lemez 50mm',
    'price' => 2990,                    // Egysegar HUF-ban
    'quantity' => 2,
    'attributes' => [
        'product_id' => 123,
        'unit_id' => 5,
        'unit_name' => 'm2',
        'product_attributes' => [],     // Jovobeni: ['color' => 'white', 'size' => 'XL']
        'image_url' => '/storage/product-images/...',
        'slug' => 'hoszigetelo-lemez-50mm',
    ],
    'associatedModel' => Product::class,
]);
```

---

## 3. Letrehozando fajlok

### 3.1 CartService - Uzleti logika

**Fajl:** `app/Services/CartService.php`

Metodusok:
- `addItem(Product $product, int $quantity, ?int $unitId, array $productAttributes)` - Termek hozzaadasa
- `updateQuantity(string $rowId, int $quantity)` - Mennyiseg frissites
- `removeItem(string $rowId)` - Termek torlese
- `getContent()` - Kosar tartalom
- `getItemCount()` - Termekek szama
- `getTotalQuantity()` - Osszes mennyiseg
- `getSubTotal()` - Reszosszeg
- `getTotal()` - Vegosszeg
- `clear()` - Kosar uritese
- `isEmpty()` - Ures-e a kosar

---

### 3.2 CartCounter - Header badge

**Fajl:** `app/Livewire/Shop/Cart/CartCounter.php`

- Megjeleníti a kosárban lévő termékek számát
- Real-time frissül a `cart-updated` eseményre
- Link a kosár oldalra

```php
#[On('cart-updated')]
public function refreshCart(CartService $cartService): void
{
    $this->count = $cartService->getItemCount();
    $this->total = $cartService->getSubTotal();
}
```

---

### 3.3 AddToCartButton - Kosarba gomb

**Fajl:** `app/Livewire/Shop/Cart/AddToCartButton.php`

Tulajdonsagok:
- `$product` - A termek
- `$quantity` - Mennyiseg (default: 1)
- `$unitId` - Kivalasztott egyseg
- `$maxQuantity` - Maximum keszlet
- `$productAttributes` - Attributumok (jovobeni)

Metodusok:
- `incrementQuantity()` / `decrementQuantity()` - Mennyiseg valtoztatas
- `addToCart()` - Kosarba helyezes

Esemenyek:
- `dispatch('cart-updated')` - Kosar frissult
- `dispatch('item-added-to-cart', [...])` - Modal megnyitas

---

### 3.4 CartModal - Felugro ablak

**Fajl:** `app/Livewire/Shop/Cart/CartModal.php`

Tulajdonsagok:
- `$show` - Modal lathato-e
- `$productId`, `$productName`, `$productImage` - Hozzaadott termek adatai
- `$quantity`, `$unitName`, `$price` - Mennyiseg es ar
- `$cartItemCount`, `$cartTotal` - Kosar osszesito
- `$upsellProducts` - Ajanlott termekek (3 db featured termek)

Metodusok:
- `openModal(array $data)` - Modal megnyitasa `#[On('item-added-to-cart')]`
- `closeModal()` - Modal bezarasa
- `goToCart()` - Atiranyitas a kosar oldalra
- `loadUpsellProducts()` - Ajanlott termekek betoltese

---

### 3.5 CartPage - Kosar oldal

**Fajl:** `app/Livewire/Shop/Cart/CartPage.php`

Funkciok:
- Kosar elemek listazasa (kep, nev, egysegar, mennyiseg, reszosszeg)
- Mennyiseg modositas (+/- gombok, input mezo)
- Termek torlese
- Kosar uritese
- Osszegzes panel (reszosszeg, szallitas, vegosszeg)
- "Tovabb a penztarhoz" gomb (kesobb checkout)
- "Vasarlas folytatasa" link

---

## 4. Modositando fajlok

### 4.1 Header - CartCounter beillesztese

**Fajl:** `resources/views/partials/shop/header.blade.php`

Csere:
```blade
{{-- Regi: @include('partials.shop.cart-chip') --}}
<livewire:shop.cart.cart-counter />
```

### 4.2 Termek oldal - AddToCartButton beillesztese

**Fajl:** `resources/views/shop/product.blade.php`

Csere (a teljes `<form>` blokk helyett):
```blade
<livewire:shop.cart.add-to-cart-button :product="$product" />
```

### 4.3 Layout - CartModal beillesztese

**Fajl:** `resources/views/components/layouts/shop.blade.php`

Hozzaadas a `</body>` elott:
```blade
<livewire:shop.cart.cart-modal />
```

### 4.4 Routes

**Fajl:** `routes/web.php`

```php
use App\Livewire\Shop\Cart\CartPage;

Route::get('/kosar', CartPage::class)->name('cart.index');
```

---

## 5. Esemeny folyamat

```
Felhasznalo kattint "Kosarba" gombra
        |
        v
AddToCartButton::addToCart()
        |
        +-- CartService::addItem()
        |       |
        |       +-- Cart::add() (Darryldecode)
        |       +-- Visszaadja rowId + item
        |
        +-- dispatch('cart-updated')
        |       |
        |       v
        |   CartCounter::refreshCart() [#[On('cart-updated')]]
        |       +-- Frissiti count & total
        |
        +-- dispatch('item-added-to-cart', [...])
                |
                v
            CartModal::openModal() [#[On('item-added-to-cart')]]
                +-- Beallitja termek adatokat
                +-- Betolti upsell termekeket
                +-- show = true (modal megnyilik)
```

---

## 6. Megvalositasi sorrend

### Fazis 1: Alapok
1. `CartService.php` letrehozasa
2. Alap tesztek irasa

### Fazis 2: Komponensek
3. `CartCounter` komponens (egyszerubb, teszteli a reaktivitast)
4. `AddToCartButton` komponens
5. `CartModal` komponens
6. `CartPage` komponens

### Fazis 3: Integracio
7. Header frissitese (`CartCounter`)
8. Termek oldal frissitese (`AddToCartButton`)
9. Layout frissitese (`CartModal`)
10. Route hozzaadasa

### Fazis 4: Teszteles
11. Feature tesztek Livewire komponensekhez
12. CSS stilusok finomhangolasa
13. End-to-end teszteles
14. `vendor/bin/pint --dirty` futtatasa

---

## 7. Kritikus fajlok

| Cél | Útvonal |
|-----|---------|
| Product model | `app/Models/Shop/Product.php` |
| Cart config | `config/shopping_cart.php` |
| Termék oldal | `resources/views/shop/product.blade.php` |
| Header | `resources/views/partials/shop/header.blade.php` |
| Shop layout | `resources/views/components/layouts/shop.blade.php` |
| Routes | `routes/web.php` |

---

## 8. Jovobeni bovitesek (elokeszitve)

### 8.1 Termek attributumok/variaciok
A `productAttributes` tomb keszen all a variaciok kezelesere:
```php
$productAttributes = [
    'color' => 'white',
    'size' => '50mm',
    'material' => 'EPS',
];
```

### 8.2 Vendeg vs bejelentkezett felhasznalok
- Adatbazis alapu kosar tarolás
- Kosar egyesites bejelentkezeskor
- Felhasznalo-specifikus kosar peldanyok

### 8.3 Keszlet validacio
Fizetes elott keszlet ellenorzes:
```php
public function validateStock(): array
{
    $issues = [];
    foreach (Cart::getContent() as $item) {
        $product = Product::find($item->attributes->product_id);
        if ($product->qty < $item->quantity) {
            $issues[] = [
                'rowId' => $item->id,
                'available' => $product->qty,
                'requested' => $item->quantity,
            ];
        }
    }
    return $issues;
}
```

---

## 9. Verifikacio

### Teszteles lépései:
1. `php artisan serve` - Szerver inditasa
2. Termek oldal megnyitasa (`/termek/{slug}`)
3. "Kosarba" gombra kattintas
4. Ellenorzes: Modal megjelenik-e a termek adataival
5. Ellenorzes: Header kosár szám frissül-e
6. "Kosar megtekintese" gombra kattintas
7. Ellenorzes: `/kosar` oldal megjelenik-e
8. Mennyiseg modositas tesztelese
9. Termek torles tesztelese
10. A teszt nem törölhet az adatbázisból!
11. `php artisan test --filter=Cart` - Tesztek futtatasa -