# Mértékegység-konverter rendszer

A `shop_product_unit_configs` tábla alapján működő rendszer lehetővé teszi, hogy egy termékhez
megadd az alap mértékegységet (pl. m²), a másodlagos csomagolási egységet (pl. bála), az
egységárat, a minimális rendelési mennyiséget és a rendelési lépésközt. A kosárban és a
terméklapon az ár és a mennyiség automatikusan számolódik, a rendszer mindig egész
csomagra (bálára) kerekít felfelé.

---

## Hogyan működik

### Adatbázis-struktúra

```
shop_product_unit_configs
├── shop_product_id       → shop_products.id (unique – termékenként 1 sor)
├── base_unit_id          → shop_units.id  (pl. m², fm, kg)
├── secondary_unit_id     → shop_units.id  (pl. bála, tekercs – opcionális)
├── secondary_unit_qty    → hány alap egység = 1 másodlagos (pl. 15.00 m²/bála)
├── min_order_qty         → minimálisan rendelhető alap egység (pl. 15.0000)
├── order_step            → rendelési lépésköz alap egységben (pl. 15.0000)
├── price_per_base_unit   → Ft / alap egység (NULL → a products.price-t használja)
└── notes                 → belső megjegyzés (opcionális)
```

### Logika terméklapon

1. Ha van `unitConfig`, a terméklapon az **egységár** és a **kerekített teljes ár** jelenik meg.
2. Az ügyfél vagy alap egységben (m²) vagy másodlagos egységben (bála) adja meg a mennyiséget –
   a gomb felváltva mutatja a két egységet.
3. A `roundUpToStep()` mindig **felfelé kerekít** egész csomagra:
   - Ha van másodlagos egység: `ceil(kért_m² / secondary_unit_qty) × secondary_unit_qty`
   - Ha csak lépésköz: `ceil(kért_qty / order_step) × order_step`
4. Ha a rendszer kerekített, sárga figyelmeztető csík jelenik meg.
5. A terméklapon az ár élőben frissül, ahogy a mennyiséget változtatják.

### Logika a kosárban

- A kosárba mindig a **kerekített alap egységben** kerül be a mennyiség (pl. 30 m², nem 2 bála).
- A kosár `attributes` mezőjében tárolódik a `secondary_qty` (pl. 2), `secondary_unit` (pl. „bála"),
  `base_unit` (pl. „m²") – ezek megjelenítési célokat szolgálnak.
- Az ár = `price_per_base_unit × actualBaseQty`.

---

## Egységek kezelése (admin)

Az egységek a **Products → Units** oldalon kezelhetők.

### Fontos mezők

| Mező | Leírás | Példa |
|---|---|---|
| Név | Belső azonosító | `negyzetmeter` |
| Rövid jelölés (`label_short`) | A vevő felé megjelenő jelölés | `m²` |
| Megjelenített név (`label`) | Hosszabb megnevezés | `Négyzetméter` |
| Alap egység (`is_base_unit`) | Ha be van kapcsolva, az egységár ehhez kötődik | ✓ |
| Sorrend | Megjelenítési sorrend | `10` |

> **Figyelem:** Az alap egységek (m², fm, db, kg stb.) esetén kapcsold be az **Alap egység**
> kapcsolót. Ezek jelennek meg az egységár-mezőnél a termékkonfigurációban.

---

## Mértékegység-konfiguráció hozzáadása egy termékhez (admin)

1. Nyisd meg a terméket: **Products → Products → [termék szerkesztése]**
2. Görgess le a **„Mértékegység & Rendelési beállítások"** szekcióhoz (a jobb oldali hasábban).
3. Töltsd ki a mezőket:

| Mező | Kötelező | Leírás |
|---|---|---|
| Alap egység | ✓ | Válassz egy `is_base_unit = true` egységet (pl. m²) |
| Egységár (Ft) | — | Ár alap egységenként. Ha üres, a termék általános árát használja. |
| Min. mennyiség | ✓ | Legkisebb rendelhető mennyiség alap egységben (pl. `15`) |
| Lépésköz | ✓ | Mennyivel lehet növelni/csökkenteni (pl. `15`) |
| Másodlagos egység | — | Ha a termék csomagban kapható (pl. bála), válassz egységet |
| 1 [másodlagos] = X alap egység | — | Hány m² van egy bálában (pl. `15`) |
| Megjegyzés | — | Belső megjegyzés, a vevő nem látja |

4. Kattints a **Mentés** gombra. A konfiguráció azonnal élesbe lép a terméklapon.

### Tipikus beállítás – szigetelő tekercs (15 m²/tekercs)

```
Alap egység:            m²
Egységár:               890
Min. mennyiség:         15
Lépésköz:               15
Másodlagos egység:      tekercs
1 tekercs = X m²:       15
```

### Tipikus beállítás – folyóméterben mért termék, csomag nélkül

```
Alap egység:            fm
Egységár:               1200
Min. mennyiség:         1
Lépésköz:               0.5
Másodlagos egység:      (üres)
```

---

## Tömeges felvitel (SQL)

Ha egyszerre sok terméket kell konfigurálni, az alábbi SQL-t futtasd közvetlenül az adatbázison
(pl. phpMyAdmin, TablePlus, vagy `php artisan tinker`).

### 1. Megnézni a meglévő egységek ID-it

```sql
SELECT id, slug, label_short, is_base_unit FROM shop_units ORDER BY sort_order;
```

Tipikus eredmény:

| id | slug | label_short | is_base_unit |
|----|------|-------------|--------------|
| 1  | negyzetmeter | m² | 1 |
| 2  | folyometer   | fm | 1 |
| 3  | db           | db | 1 |
| 4  | bala         | bála | 0 |
| 5  | tekercs      | tekercs | 0 |

### 2. Termék ID-k lekérése

```sql
SELECT id, name, sku FROM shop_products WHERE name LIKE '%szigetelő%';
```

### 3. Tömeges INSERT / UPDATE

```sql
-- Új sorok felvitele (ha még nincs konfiguráció)
INSERT INTO shop_product_unit_configs
    (shop_product_id, base_unit_id, secondary_unit_id, secondary_unit_qty,
     min_order_qty, order_step, price_per_base_unit, notes, created_at, updated_at)
VALUES
    (101, 1, 4, 15.0000, 15.0000, 15.0000, 890.00, 'XPS 10cm', NOW(), NOW()),
    (102, 1, 4, 10.0000, 10.0000, 10.0000, 1050.00, 'EPS 15cm', NOW(), NOW()),
    (103, 2, NULL, NULL,  1.0000,  0.5000,  650.00, 'Párasp. fólia', NOW(), NOW());
```

```sql
-- Meglévő konfiguráció frissítése
UPDATE shop_product_unit_configs
SET price_per_base_unit = 950.00,
    updated_at = NOW()
WHERE shop_product_id IN (101, 102);
```

```sql
-- Ellenőrzés
SELECT p.name, p.sku, c.*, bu.label_short AS base, su.label_short AS secondary
FROM shop_product_unit_configs c
JOIN shop_products p ON p.id = c.shop_product_id
JOIN shop_units bu ON bu.id = c.base_unit_id
LEFT JOIN shop_units su ON su.id = c.secondary_unit_id
ORDER BY p.name;
```

### 4. Tinker segítségével (ha nem érhető el közvetlen DB-hozzáférés)

```bash
php artisan tinker
```

```php
use App\Models\Shop\Product;
use App\Models\Shop\ProductUnitConfig;
use App\Models\Shop\Unit;

$m2    = Unit::where('slug', 'negyzetmeter')->first();
$bala  = Unit::where('slug', 'bala')->first();

$products = [
    ['sku' => 'XPS-001', 'step' => 15, 'price' => 890],
    ['sku' => 'EPS-002', 'step' => 10, 'price' => 1050],
];

foreach ($products as $row) {
    $product = Product::where('sku', $row['sku'])->firstOrFail();
    ProductUnitConfig::updateOrCreate(
        ['shop_product_id' => $product->id],
        [
            'base_unit_id'       => $m2->id,
            'secondary_unit_id'  => $bala->id,
            'secondary_unit_qty' => $row['step'],
            'min_order_qty'      => $row['step'],
            'order_step'         => $row['step'],
            'price_per_base_unit'=> $row['price'],
        ]
    );
    echo "OK: {$product->name}\n";
}
```

---

## Konfiguráció eltávolítása

### Egy termékről (admin)

Nyisd meg a terméket szerkesztésre, görgess a **„Mértékegység & Rendelési beállítások"**
szekcióhoz, és töröld ki az összes mezőt (állítsd üresre), majd mentsd. A Filament a
kapcsolódó `shop_product_unit_configs` sort törli, mert a `Fieldset::make()->relationship()`
null-mentésnél törli a rekordot.

> Ha ez valamiért nem törli automatikusan, használd az SQL módszert lentebb.

### Egy termékről (SQL)

```sql
DELETE FROM shop_product_unit_configs
WHERE shop_product_id = (SELECT id FROM shop_products WHERE sku = 'XPS-001');
```

### Több termékről (SQL)

```sql
DELETE FROM shop_product_unit_configs
WHERE shop_product_id IN (101, 102, 103);
```

### Az összes konfigurációt törölni

```sql
TRUNCATE TABLE shop_product_unit_configs;
```

### A teljes rendszert eltávolítani (visszaállítás)

Ha a mértékegység-konverter rendszert teljesen ki akarod venni:

```bash
# 1. Migráció visszaállítása (törli a shop_product_unit_configs táblát és a shop_units extra oszlopait)
php artisan migrate:rollback --step=2

# 2. Érintett fájlok visszaállítása git-tel (ha szükséges)
git checkout app/Models/Shop/Product.php
git checkout app/Models/Shop/Unit.php
git checkout app/Services/CartService.php
git checkout app/Livewire/Shop/Cart/AddToCartButton.php
git checkout resources/views/livewire/shop/cart/add-to-cart-button.blade.php
git checkout resources/views/shop/product.blade.php

# 3. Törölni az új fájlokat
rm app/Models/Shop/ProductUnitConfig.php
rm app/Http/Controllers/ProductUnitCalcController.php
```

---

## API végpont (fejlesztői referencia)

```
GET /api/products/{product}/unit-calc?qty=30&unit=base
GET /api/products/{product}/unit-calc?qty=2&unit=secondary
```

**Válasz:**

```json
{
    "actual_base_qty": 30,
    "secondary_qty": 2,
    "base_unit_label": "m²",
    "secondary_unit_label": "bála",
    "price_per_base_unit": 890,
    "total_price": 26700,
    "was_rounded_up": false
}
```

| Mező | Leírás |
|---|---|
| `actual_base_qty` | Felkerekített tényleges mennyiség alap egységben |
| `secondary_qty` | Hány másodlagos egység (bála) szükséges |
| `was_rounded_up` | `true`, ha a rendszer kerekített felfelé |
| `total_price` | `actual_base_qty × price_per_base_unit` |

---

## Érintett fájlok

| Fájl | Szerep |
|---|---|
| `app/Models/Shop/ProductUnitConfig.php` | Model, `roundUpToStep()`, `toSecondaryUnit()` |
| `app/Models/Shop/Product.php` | `unitConfig()` HasOne reláció |
| `app/Models/Shop/Unit.php` | `is_base_unit`, `label_short` mezők |
| `app/Services/CartService.php` | `addItem()` – `secondary_qty`, `base_unit` attributes |
| `app/Livewire/Shop/Cart/AddToCartButton.php` | Mennyiség-kezelés, kerekítés, esemény-dispatch |
| `app/Http/Controllers/ProductUnitCalcController.php` | REST API kalkulátor |
| `app/Filament/.../Products/Schemas/ProductForm.php` | Admin form szekció |
| `app/Filament/.../Units/Schemas/UnitForm.php` | Admin unit-kezelő mezők |
| `resources/views/livewire/shop/cart/add-to-cart-button.blade.php` | Frontend komponens |
| `resources/views/shop/product.blade.php` | Terméklap – ár-blokk Alpine.js-szel |
| `resources/css/shop.css` | Pill toggle, price summary stílusok |
| `database/migrations/2026_03_30_151651_*` | `shop_units` bővítése |
| `database/migrations/2026_03_30_151659_*` | `shop_product_unit_configs` tábla |
