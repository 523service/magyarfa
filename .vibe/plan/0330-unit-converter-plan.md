# Termék Mértékegység Váltó Rendszer – Fejlesztési Terv

**Stack:** Laravel (PHP) + MySQL  
**Érintett termékek:** ~200 db, főleg hőszigetelők  
**Dátum:** 2026-03-30

---

## 1. Összefoglaló és alapelvek

A rendszer célja, hogy minden termékhez meghatározható legyen:
- az **alap mértékegység** (pl. `m2`) és a hozzá tartozó ár
- egy **másodlagos mértékegység** (pl. `bála`) és az átváltási arány
- a **minimális rendelési mennyiség** és a **lépésköz** (alap egységben)
- a frontend ezekből dinamikusan számolja az árat és mutatja mindkét egységet

### Alapelvek
- Az ár **mindig az alap egységben (pl. m2) van tárolva**
- A bála/csomag csak egy átváltási nézet, nem külön ár
- Ha a felhasználó m2-ben ad meg nem kerek mennyiséget → **felfelé kerekítés** a következő teljes bálára
- A rendszer extensible: `m2`, `fm`, `db`, `kg`, `m3`, `L`, `zsák`, `bála`, `csomag` és egyéb egységek is kezelhetők

---

## 2. Adatbázis struktúra

### 2.1 `unit_types` – Egységek referencia táblája

```sql
CREATE TABLE unit_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50)  NOT NULL,          -- 'bála', 'm2', 'fm', 'db', 'kg', 'm3', 'L', 'zsák'
    label           VARCHAR(50)  NOT NULL,          -- megjelenített név: 'bála', 'm²', 'folyóméter'
    label_short     VARCHAR(20)  NOT NULL,          -- rövid: 'bála', 'm²', 'fm'
    is_base_unit    TINYINT(1)   DEFAULT 0,         -- 1 ha alap egység (m2, fm, db stb.)
    sort_order      INT          DEFAULT 0,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Alap feltöltés
INSERT INTO unit_types (name, label, label_short, is_base_unit, sort_order) VALUES
('m2',    'm²',          'm²',    1, 1),
('fm',    'folyóméter',  'fm',    1, 2),
('db',    'darab',       'db',    1, 3),
('kg',    'kilogramm',   'kg',    1, 4),
('m3',    'm³',          'm³',    1, 5),
('L',     'liter',       'L',     1, 6),
('zsak',  'zsák',        'zsák',  0, 7),
('bala',  'bála',        'bála',  0, 8),
('csomag','csomag',      'csomag',0, 9);
```

### 2.2 `product_unit_config` – Termékenkénti egység konfiguráció

```sql
CREATE TABLE product_unit_config (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id              INT UNSIGNED NOT NULL,          -- FK -> products.id
    base_unit_id            INT UNSIGNED NOT NULL,          -- FK -> unit_types.id (pl. m2)
    secondary_unit_id       INT UNSIGNED NULL,              -- FK -> unit_types.id (pl. bála) – NULL ha nincs
    secondary_unit_qty      DECIMAL(10,4) NULL,             -- hány alap egység = 1 másodlagos (pl. 25.0000 m2/bála)
    min_order_qty           DECIMAL(10,4) NOT NULL DEFAULT 1, -- minimum rendelhető alap egység
    order_step              DECIMAL(10,4) NOT NULL DEFAULT 1, -- lépésköz alap egységben
    price_per_base_unit     DECIMAL(12,2) NULL,             -- NULL = a products táblából jön
    notes                   VARCHAR(255)  NULL,             -- pl. '1cm vastag EPS, 25m2/bála'
    created_at              TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_product (product_id),
    INDEX idx_product_id (product_id)
);
```

**Példa sorok:**

| product_id | base_unit | secondary_unit | secondary_unit_qty | min_order_qty | order_step |
|------------|-----------|----------------|--------------------|---------------|------------|
| 42 (EPS 1cm) | m2      | bála           | 25.00              | 25.00         | 25.00      |
| 43 (EPS 2cm) | m2      | bála           | 12.50              | 12.50         | 12.50      |
| 87 (ragasztó) | zsák   | NULL           | NULL               | 1.00          | 1.00       |
| 112 (üveggyapot) | fm  | bála           | 50.00              | 50.00         | 50.00      |

---

## 3. Backend – Laravel

### 3.1 Model

```php
// app/Models/ProductUnitConfig.php

class ProductUnitConfig extends Model
{
    protected $fillable = [
        'product_id', 'base_unit_id', 'secondary_unit_id',
        'secondary_unit_qty', 'min_order_qty', 'order_step',
        'price_per_base_unit', 'notes'
    ];

    public function product()      { return $this->belongsTo(Product::class); }
    public function baseUnit()     { return $this->belongsTo(UnitType::class, 'base_unit_id'); }
    public function secondaryUnit(){ return $this->belongsTo(UnitType::class, 'secondary_unit_id'); }

    /**
     * Mennyi bála/csomag a megadott alap mennyiség?
     * Mindig felfelé kerekít.
     */
    public function toSecondaryUnit(float $baseQty): ?float
    {
        if (!$this->secondary_unit_qty) return null;
        return ceil($baseQty / $this->secondary_unit_qty);
    }

    /**
     * Felfelé kerekített tényleges rendelési mennyiség (alap egységben)
     */
    public function roundUpToStep(float $requestedQty): float
    {
        if (!$this->secondary_unit_qty) {
            // Nincs bála-váltó, csak lépésköz kerekítés
            return ceil($requestedQty / $this->order_step) * $this->order_step;
        }
        $balas = ceil($requestedQty / $this->secondary_unit_qty);
        return $balas * $this->secondary_unit_qty;
    }
}
```

### 3.2 API endpoint – ár és egység kalkuláció

```php
// routes/api.php
Route::get('/products/{product}/unit-calc', [ProductUnitCalcController::class, 'calc']);

// app/Http/Controllers/ProductUnitCalcController.php
public function calc(Request $request, Product $product)
{
    $config = $product->unitConfig; // eager load
    if (!$config) return response()->json(['error' => 'No unit config'], 404);

    $requestedQty  = (float) $request->input('qty', $config->min_order_qty);
    $selectedUnit  = $request->input('unit', $config->baseUnit->name); // 'base' vagy 'secondary'

    // Ha bálában adja meg → visszaváltás alap egységre
    if ($selectedUnit === 'secondary' && $config->secondary_unit_qty) {
        $requestedQty = $requestedQty * $config->secondary_unit_qty;
    }

    $actualQty    = $config->roundUpToStep($requestedQty);
    $pricePerUnit = $config->price_per_base_unit ?? $product->price;
    $totalPrice   = $actualQty * $pricePerUnit;
    $secondaryQty = $config->toSecondaryUnit($actualQty);

    return response()->json([
        'actual_base_qty'      => $actualQty,
        'secondary_qty'        => $secondaryQty,
        'base_unit_label'      => $config->baseUnit->label_short,
        'secondary_unit_label' => $config->secondaryUnit?->label_short,
        'price_per_base_unit'  => $pricePerUnit,
        'total_price'          => $totalPrice,
        'was_rounded_up'       => ($actualQty > $requestedQty),
    ]);
}
```

---

## 4. Frontend – product-price-block

### 4.1 HTML struktúra

```html
<div id="product-price-block" class="product-price-block">

    <!-- Mennyiség választó -->
    <div class="quantity-selector">
        <button class="qty-btn qty-minus">−</button>
        <input type="number"
               id="qty-input"
               value="{{ $config->min_order_qty }}"
               min="{{ $config->min_order_qty }}"
               step="{{ $config->order_step }}"
               data-min="{{ $config->min_order_qty }}"
               data-step="{{ $config->order_step }}"
               data-secondary-qty="{{ $config->secondary_unit_qty }}"
               data-price="{{ $product->price }}"
               data-base-unit="{{ $config->baseUnit->label_short }}"
               data-secondary-unit="{{ $config->secondaryUnit?->label_short }}"
        >
        <button class="qty-btn qty-plus">+</button>

        <!-- Egység váltó (csak ha van másodlagos egység) -->
        @if($config->secondaryUnit)
        <select id="unit-selector" class="unit-selector">
            <option value="base">{{ $config->baseUnit->label_short }}</option>
            <option value="secondary">{{ $config->secondaryUnit->label_short }}</option>
        </select>
        @endif
    </div>

    <!-- Ár megjelenítés -->
    <div class="price-display">
        <div class="total-price">
            <span id="total-price-value"><!-- JS tölti --></span>
            <span class="currency">Ft</span>
        </div>
        <div class="price-breakdown" id="price-breakdown">
            <!-- JS tölti: "25 m² · 1 bála" -->
        </div>
        <div class="unit-price" id="unit-price">
            <!-- JS tölti: "260 Ft / m²" -->
        </div>
        @if($config->secondaryUnit)
        <div class="rounding-notice" id="rounding-notice" style="display:none">
            ⚠️ Felfelé kerekítve: teljes bálát szállítunk
        </div>
        @endif
    </div>

    <button class="add-to-cart-btn" id="add-to-cart">Kosárba</button>
</div>
```

### 4.2 JavaScript logika

```javascript
// resources/js/product-unit-calc.js

document.addEventListener('DOMContentLoaded', () => {
    const input       = document.getElementById('qty-input');
    const unitSel     = document.getElementById('unit-selector');
    const totalEl     = document.getElementById('total-price-value');
    const breakdownEl = document.getElementById('price-breakdown');
    const unitPriceEl = document.getElementById('unit-price');
    const roundNotice = document.getElementById('rounding-notice');

    if (!input) return;

    const cfg = {
        minQty:        parseFloat(input.dataset.min),
        step:          parseFloat(input.dataset.step),
        secondaryQty:  parseFloat(input.dataset.secondaryQty) || null,
        price:         parseFloat(input.dataset.price),
        baseUnit:      input.dataset.baseUnit,
        secondaryUnit: input.dataset.secondaryUnit || null,
    };

    function formatPrice(n) {
        return new Intl.NumberFormat('hu-HU').format(Math.round(n));
    }

    function recalculate() {
        let inputQty   = parseFloat(input.value) || cfg.minQty;
        const unit     = unitSel ? unitSel.value : 'base';

        // Bálában megadott → m2-re visszaváltás
        let baseQty = (unit === 'secondary' && cfg.secondaryQty)
            ? inputQty * cfg.secondaryQty
            : inputQty;

        // Felfelé kerekítés bálára
        let actualBaseQty = baseQty;
        let wasRounded    = false;

        if (cfg.secondaryQty) {
            const balas    = Math.ceil(baseQty / cfg.secondaryQty);
            actualBaseQty  = balas * cfg.secondaryQty;
            wasRounded     = actualBaseQty > baseQty;
        } else {
            // Csak lépésköz kerekítés
            actualBaseQty = Math.ceil(baseQty / cfg.step) * cfg.step;
        }

        const secondaryQtyDisplay = cfg.secondaryQty
            ? Math.round(actualBaseQty / cfg.secondaryQty)
            : null;

        const totalPrice = actualBaseQty * cfg.price;

        // Megjelenítés
        totalEl.textContent = formatPrice(totalPrice);

        // Breakdown: "25 m² · 1 bála"
        let breakdown = `${actualBaseQty} ${cfg.baseUnit}`;
        if (secondaryQtyDisplay !== null) {
            breakdown += ` &nbsp;·&nbsp; ${secondaryQtyDisplay} ${cfg.secondaryUnit}`;
        }
        breakdownEl.innerHTML = breakdown;

        // Egységár
        unitPriceEl.textContent = `${formatPrice(cfg.price)} Ft / ${cfg.baseUnit}`;

        // Kerekítési figyelmeztetés
        if (roundNotice) {
            roundNotice.style.display = wasRounded ? 'block' : 'none';
        }
    }

    // +/- gombok
    document.querySelector('.qty-minus')?.addEventListener('click', () => {
        const unit = unitSel?.value || 'base';
        const step = (unit === 'secondary') ? 1 : cfg.step;
        input.value = Math.max(cfg.minQty, parseFloat(input.value) - step);
        recalculate();
    });

    document.querySelector('.qty-plus')?.addEventListener('click', () => {
        const unit = unitSel?.value || 'base';
        const step = (unit === 'secondary') ? 1 : cfg.step;
        input.value = parseFloat(input.value) + step;
        recalculate();
    });

    input.addEventListener('change', recalculate);
    unitSel?.addEventListener('change', () => {
        // Egység váltáskor reset a minimum értékre
        const unit = unitSel.value;
        if (unit === 'secondary') {
            input.value = 1;
            input.step  = 1;
            input.min   = 1;
        } else {
            input.value = cfg.minQty;
            input.step  = cfg.step;
            input.min   = cfg.minQty;
        }
        recalculate();
    });

    // Kezdeti kalkuláció
    recalculate();
});
```

---

## 5. Admin felület

### 5.1 Termék szerkesztő – új szekció

Az admin termék-szerkesztő oldalon egy új **"Mértékegység & Rendelési beállítások"** szekció:

```
┌─────────────────────────────────────────────────────┐
│  Mértékegység & Rendelési beállítások               │
├──────────────────┬──────────────────────────────────┤
│ Alap egység      │ [m²          ▼]                  │
│ Egységár (Ft)    │ [260              ]               │
│ Min. mennyiség   │ [25               ] m²            │
│ Lépésköz         │ [25               ] m²            │
├──────────────────┼──────────────────────────────────┤
│ Másodlagos egység│ [bála         ▼]  (opcionális)   │
│ 1 bála =         │ [25               ] m²            │
│ Megjegyzés       │ [1cm vastag EPS       ]           │
└──────────────────┴──────────────────────────────────┘
```

---

## 6. Tömeges feltöltés – MySQL update script (Google Sheets → SQL)

### 6.1 Google Sheets struktúra

A Sheets-ben a következő oszlopok kellenek:

| A: product_id | B: base_unit | C: secondary_unit | D: secondary_unit_qty | E: min_order_qty | F: order_step | G: price | H: notes |
|---------------|--------------|-------------------|-----------------------|------------------|---------------|----------|----------|
| 42            | m2           | bala              | 25                    | 25               | 25            | 260      | EPS 1cm  |
| 43            | m2           | bala              | 12.5                  | 12.5             | 12.5          | 340      | EPS 2cm  |

### 6.2 Google Sheets formula – SQL generálás

Az `I` oszlopba ezt a formulát írd (I2-től húzd le):

```
=IF(A2="","",
"INSERT INTO product_unit_config
  (product_id, base_unit_id, secondary_unit_id, secondary_unit_qty, min_order_qty, order_step, price_per_base_unit, notes)
VALUES ("
&A2&",
  (SELECT id FROM unit_types WHERE name='"&B2&"'),
  "&IF(C2="","NULL","(SELECT id FROM unit_types WHERE name='"&C2&"')")&",
  "&IF(D2="","NULL",D2)&", "&E2&", "&F2&", "&IF(G2="","NULL",G2)&",
  '"&H2&"')
ON DUPLICATE KEY UPDATE
  base_unit_id = VALUES(base_unit_id),
  secondary_unit_id = VALUES(secondary_unit_id),
  secondary_unit_qty = VALUES(secondary_unit_qty),
  min_order_qty = VALUES(min_order_qty),
  order_step = VALUES(order_step),
  price_per_base_unit = VALUES(price_per_base_unit),
  notes = VALUES(notes),
  updated_at = NOW();")
```

> Az `ON DUPLICATE KEY UPDATE` miatt ugyanaz a script újra futtatható, biztonságosan frissít.

### 6.3 Batch futtatás

```sql
-- 1. Ellenőrzés futtatás előtt
SELECT p.id, p.name, puc.base_unit_id, puc.secondary_unit_qty
FROM products p
LEFT JOIN product_unit_config puc ON p.id = puc.product_id
WHERE puc.id IS NULL
ORDER BY p.id;

-- 2. Script futtatás (a Sheets-ből generált INSERT-ek)
-- ...

-- 3. Ellenőrzés futtatás után
SELECT COUNT(*) as konfiguralt FROM product_unit_config;
```

---

## 7. Implementációs sorrend

### Fázis 1 – Adatbázis (1-2 óra)
- [ ] `unit_types` tábla létrehozása és feltöltése
- [ ] `product_unit_config` tábla létrehozása
- [ ] Migration fájlok megírása

### Fázis 2 – Backend (3-4 óra)
- [ ] `UnitType` model
- [ ] `ProductUnitConfig` model + `roundUpToStep()` metódus
- [ ] `Product` modellbe: `hasOne(ProductUnitConfig)`
- [ ] API endpoint: `GET /api/products/{id}/unit-calc`
- [ ] Admin CRUD: `ProductUnitConfigController`

### Fázis 3 – Admin UI (2-3 óra)
- [ ] Új szekció a termék szerkesztőben
- [ ] Egység dropdown (unit_types alapján)
- [ ] Mentés / validáció

### Fázis 4 – Frontend (3-4 óra)
- [ ] `product-price-block` HTML frissítése
- [ ] `product-unit-calc.js` megírása
- [ ] Kerekítési figyelmeztetés
- [ ] Kosár integrációhoz: `actual_base_qty` átadása

### Fázis 5 – Tömeges import (2-3 óra)
- [ ] Sheets sablon elkészítése (200 termék adataival)
- [ ] SQL generálás és ellenőrzés
- [ ] Batch futtatás staging-en, majd élesben

### Fázis 6 – Tesztelés (1-2 óra)
- [ ] Egységtesztek a `roundUpToStep()` metódusra
- [ ] Frontend manuális teszt: m2 ↔ bála váltás
- [ ] Kosárba rakás, checkout összeg ellenőrzése

**Becsült összes idő: ~12-18 óra**

---

## 8. Edge case-ek és megjegyzések

| Helyzet | Kezelés |
|---------|---------|
| Nincs `secondary_unit` | Csak m2 látszik, nincs váltó |
| `price_per_base_unit` NULL a config-ban | A `products.price` mezőt használja fallback-ként |
| Felhasználó 30 m2-t ír be, lépésköz 25 | Felfelé kerekít 50-re, figyelmeztetés megjelenik |
| Bálában rendel: 2 bála | 2 × 25 = 50 m2 kerül a kosárba |
| Variant rendszer bevezetésekor | A `product_unit_config` bővíthető `variant_id` oszloppal |

---

## 9. Kosár integráció

A kosárba mindig az **alap egységben vett tényleges mennyiség** kerüljön:

```php
// CartItem tárolás
[
    'product_id'    => 42,
    'qty'           => 50.00,          // mindig m2-ben (vagy alap egységben)
    'base_unit'     => 'm2',
    'secondary_qty' => 2,              // 2 bála – megjelenítéshez
    'unit_price'    => 260.00,
    'total_price'   => 13000.00,
]
```
