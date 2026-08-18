# Related Products — Terv (2026-05-05)

## Összefoglaló

Manuálisan kezelt termék-ajánlás rendszer: ajánlott kiegészítők és helyettesítő termékek.
Admin felületen kézzel állítható be minden termékhez. Megjelenés: termék oldal, kosár oldal, kosár modal.

## Választott konfiguráció

- **Kapcsolat típusai:** Ajánlott kiegészítő, Helyettesítő termék (Szükséges kiegészítő NEM kell)
- **Admin kontroll:** Manuális kiválasztás (Filament select)
- **Mennyiség:** Nem kell (egyszerű pivot, nincs quantity mező)
- **Megjelenés:** Termék oldal + Kosár oldal + Kosár modal

---

## 1. Adatbázis — új pivot tábla

Tábla neve: `shop_related_products`

| Oszlop               | Típus               | Megjegyzés                        |
|----------------------|---------------------|-----------------------------------|
| id                   | bigint unsigned PK  |                                   |
| product_id           | FK → shop_products  |                                   |
| related_product_id   | FK → shop_products  |                                   |
| type                 | string              | 'accessory' \| 'substitute'       |
| created_at           | timestamp           |                                   |
| updated_at           | timestamp           |                                   |

- Unique constraint: `(product_id, related_product_id, type)`
- Migration: `php artisan make:migration create_shop_related_products_table`

---

## 2. Enum

**Fájl:** `app/Enums/RelatedProductType.php`

Backed string enum, `HasLabel` implementációval:
- `Accessory = 'accessory'` → "Ajánlott kiegészítő"
- `Substitute = 'substitute'` → "Helyettesítő termék"

---

## 3. Product model — új relationship-ek

**Fájl:** `../../../app/Models/Shop/Product.php`

```php
// Összes kapcsolódó termék (pivot type-pal)
public function relatedProducts(): BelongsToMany
{
    return $this->belongsToMany(Product::class, 'shop_related_products', 'product_id', 'related_product_id')
        ->withPivot('type')
        ->withTimestamps();
}

// Szűkítések
public function accessories(): BelongsToMany
{
    return $this->relatedProducts()->wherePivot('type', RelatedProductType::Accessory->value);
}

public function substitutes(): BelongsToMany
{
    return $this->relatedProducts()->wherePivot('type', RelatedProductType::Substitute->value);
}
```

---

## 4. Filament Admin — ProductForm frissítés

**Fájl:** `../../../app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php`

Új "Kapcsolódó termékek" section az Associations section után:

```php
Section::make('Kapcsolódó termékek')
    ->schema([
        Select::make('accessories')
            ->label('Ajánlott kiegészítők')
            ->relationship('accessories', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->helperText('Ezeket a termékeket ajánljuk fel a vásárlónak kiegészítőként.'),

        Select::make('substitutes')
            ->label('Helyettesítő termékek')
            ->relationship('substitutes', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->helperText('Ezek a termékek alternatívaként ajánlhatók.'),
    ]),
```

---

## 5. ProductController — `getRelatedProducts()` frissítés

**Fájl:** `../../../app/Http/Controllers/ProductController.php`

Logika:
1. Betölti a `accessories` és `substitutes` relationship-eket a termékre
2. Ha van legalább egy manuálisan beállított → azokat adja vissza
3. Ha üres → fallback a jelenlegi kategória-alapú logikára (változatlan)

A `show()` metódus az alábbi adatokat adja át a view-nak:
- `$accessories` — explicit kiegészítők (lehet üres collection)
- `$substitutes` — explicit helyettesítők (lehet üres collection)
- `$relatedProducts` — kategória-alapú fallback (ha az előző kettő üres)

---

## 6. Frontend — Termék oldal

**Fájl:** `../../../resources/views/shop/product.blade.php`

Jelenlegi "Kapcsolódó termékek" szekció bővítése:

```
Ha $accessories->isNotEmpty():
    → "Ajánlott kiegészítők" szekció (kártyák, eltérő fejléc-stílussal)

Ha $substitutes->isNotEmpty():
    → "Helyettesítő termékek" szekció

Ha mindkettő üres ($relatedProducts->isNotEmpty()):
    → Meglévő "Kapcsolódó termékek" (kategória-alapú fallback)
```

---

## 7. Kosár modal — `loadUpsellProducts()` frissítés

**Fájlok:** `../../../app/Livewire/Shop/Cart/CartModal.php`, nézet: `cart-modal.blade.php`

Frissített logika:
1. Az épp kosárba tett termék (`$this->productId`) accessories-eit tölti be
2. Ha nincs kiegészítő → fallback: featured termékek (jelenlegi viselkedés)

---

## 8. Kosár oldal — új "Ne felejtsd el!" szekció

**Fájlok:** kosár Livewire component + kosár view

Logika:
1. Kosárban lévő product ID-k alapján betölti az összes accessories-t
2. De-duplikálja (ha több termékhez is ajánlott ugyanaz)
3. Kizárja a már kosárban lévő termékeket
4. Max. 4 termék jelenik meg
5. Csak akkor jelenik meg a szekció, ha van mit mutatni

---

## 9. Tesztek

| Teszt fájl | Mit tesztel |
|---|---|
| `tests/Feature/RelatedProductsTest.php` | Accessory/substitute mentés adminból, olvasás, relationship |
| `tests/Feature/CartModalTest.php` (frissítés) | Upsell products = accessories, fallback featured |
| `tests/Feature/ProductControllerTest.php` (frissítés) | Accessories/substitutes megjelennek a termék oldalon |
| `tests/Feature/CartPageTest.php` (frissítés) | "Ne felejtsd el!" szekció megjelenik |

---

## Érintett fájlok összesítő

### Új fájlok
- `database/migrations/XXXX_create_shop_related_products_table.php`
- `app/Enums/RelatedProductType.php`
- `tests/Feature/RelatedProductsTest.php`

### Módosított fájlok
- `../../../app/Models/Shop/Product.php`
- `../../../app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php`
- `../../../app/Http/Controllers/ProductController.php`
- `../../../resources/views/shop/product.blade.php`
- `../../../app/Livewire/Shop/Cart/CartModal.php`
- `../../../resources/views/livewire/shop/cart/cart-modal.blade.php`
- Kosár Livewire component + nézet (kosár oldal)
