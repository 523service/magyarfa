# Dinamikus Alapár Rendszer – Fejlesztési Terv

**Stack:** Laravel 12 + Filament v4 + SQLite
**Érintett termékek:** ~9 db hőszigetelő (lap, lemez, tekercs, rendszerek)
**Dátum:** 2026-04-01

---

## Probléma

A hőszigetelőanyag termékek (lap, lemez, tekercs, rendszerek) árai naponta változnak.
Jelenleg manuálisan kell minden termék `shop_products.price` mezőjét frissíteni.

A cél: egyetlen helyen (anyagtípusonként) lehessen frissíteni az alapárat,
és a termékárak ebből automatikusan számolódjanak.

---

## Döntések (AskUserQuestion alapján)

| Kérdés | Válasz |
|---|---|
| Vastagság tárolása | Termék attribútumból (pl. "Vastagság" attribútum) |
| Alapár struktúra | Anyagtípusonként (Ft/m²/cm) |
| Ár érvényesítés | Dinamikusan, valós időben (price=0 esetén) |
| Rendszer képlet | Több komponens összege |

---

## Ár logika (általános)

Az általános képlet: `ár = alapár_per_egység × mennyiség`

ahol a **mennyiség** bármilyen termék attribútum értéke lehet (cm, liter, kg, stb.),
és ezt az `MaterialBasePrice` határozza meg — nem hardcode-olva.

```
price > 0                      → manuális felülírás, ezt használja
price = 0, egyszerű termék    → base_price.price_per_unit × product.getAttribute(base_price.attribute_slug)
price = 0, rendszer termék    → Σ(komponens.base_price.price_per_unit × komponens.quantity)
nincs semmi                    → 0
```

**Példák:**

| Terméktípus | attribute_slug | unit_label | Képlet |
|---|---|---|---|
| EPS lap (10 cm) | `vastagsag` | cm | 282 Ft/cm × 10 = 2 820 Ft/m² |
| Festék (5 liter) | `meret_liter` | liter | 1 500 Ft/l × 5 = 7 500 Ft |
| Ragasztó (25 kg) | `suly_kg` | kg | 320 Ft/kg × 25 = 8 000 Ft |

---

## 1. lépés — Új tábla: `material_base_prices`

```sql
id
name             string   -- "EPS standard", "Grafitos EPS", "Kőzetgyapot", "Festék alap"
slug             string   -- "eps-standard", "grafitos-eps"
price_per_unit   decimal  -- Ft/egység ← naponta frissített érték
attribute_slug   string   -- melyik termék attribútumból olvassa a mennyiséget
                          -- pl. "vastagsag", "meret_liter", "suly_kg"
unit_label       string   -- megjelenítéshez: "cm", "liter", "kg"
description      text     -- nullable
is_active        boolean  -- default true
timestamps
```

Ez az egyetlen hely ahol a napi árakat karbantartják.
Az `attribute_slug` és `unit_label` ritkán változik, az `price_per_unit` viszont akár naponta.

---

## 2. lépés — `shop_products` bővítés

```sql
material_base_price_id  FK → material_base_prices (nullable)
```

- Ha `price > 0` → manuális ár érvényes (override)
- Ha `price = 0` ÉS `material_base_price_id` be van állítva → dinamikus számítás
- A resolver maga olvassa ki a megfelelő attribútumot az `attribute_slug` alapján

---

## 3. lépés — Rendszer komponensek: `product_price_components`

Csak a rendszer típusú termékekhez. Minden komponens saját `MaterialBasePrice`-t
és saját `quantity` értéket tartalmaz — teljesen függetlenül a termék attribútumaitól.

```sql
id
shop_product_id          FK → shop_products
material_base_price_id   FK → material_base_prices
quantity                 decimal  -- mennyiség az adott egységben
                                  -- (pl. 10 ha cm, 3.5 ha liter, 25 ha kg)
label                    string   -- "EPS lemez", "Ragasztó", "Üvegszövet háló"
sort_order               integer
timestamps
```

Rendszer termék ára = Σ(komponens.materialBasePrice.price_per_unit × komponens.quantity)

A `quantity` mindig az adott `MaterialBasePrice.unit_label` egységében értendő.

---

## 4. lépés — `PriceResolverService`

`app/Services/PriceResolverService.php`

```php
public function resolve(Product $product): float
{
    // 1. Manuális felülírás – mindig érvényes
    if ($product->price > 0) {
        return (float) $product->price;
    }

    // 2. Rendszer termék – komponensek összege
    if ($product->priceComponents->isNotEmpty()) {
        return $product->priceComponents->sum(
            fn ($c) => $c->materialBasePrice->price_per_unit * $c->quantity
        );
    }

    // 3. Egyszerű termék – alapár × attribútum érték
    if ($product->materialBasePrice) {
        $slug = $product->materialBasePrice->attribute_slug;
        $qty  = $product->getAttributeNumericValue($slug); // generikus helper
        if ($qty > 0) {
            return $product->materialBasePrice->price_per_unit * $qty;
        }
    }

    return 0.0;
}
```

`Product::getAttributeNumericValue(string $slug): float`
— kiolvassa a megadott slug-ú attribútum numerikus értékét a termékhez rendelt
`shop_product_attribute_values` táblából. Általános, bármilyen attribútumra működik.

---

## 5. lépés — Filament Admin

### MaterialBasePriceResource (Products cluster)

- Lista: név, Ft/m²/cm (inline szerkesztés!), aktív
- Napi frissítés: 1 kattintás → inline edit → mentés
- Nincs szükség külön szerkesztő oldalra

### ProductForm bővítés

- `material_base_price_id` Select (anyagtípus kiválasztása)
- Számított ár előnézet pl.: *"Számított ár: 2 820 Ft/m²"* (ha price=0 és van alapár)

### Rendszer termék – RelationManager

- `PriceComponentsRelationManager`
- Komponensek hozzáadása: anyagtípus + vastagság + label
- Sorrend drag-and-drop

---

## 6. lépés — Integrációs pontok

A `PriceResolverService` bekötési helyei:

| Hol | Mit vált ki |
|---|---|
| `ProductController@show` | Termék oldal megjelenített ára |
| `CartService::addItem()` | Kosárba rakásnál az ár |
| `UnitConfig` | Ha `price_per_base_unit` null, resolver adja az alapot |
| `Product::toSearchableArray()` | Keresési index ára |
| Filament ProductForm preview | Számított ár előnézet |

---

## Bővíthetőség

Ha pl. festék, ragasztó, vagy bármilyen más terméktípus is ezt a rendszert használja:

1. Felvenni egy új `MaterialBasePrice` rekordot
   - name: "Festék alap", price_per_unit: 1500, attribute_slug: `meret_liter`, unit_label: "liter"
2. A termékhez rendelni `material_base_price_id`-t az adminban
3. Kész — a resolver automatikusan a `meret_liter` attribútum értékével számol

**Nincs szükség kódváltozásra**, csak adat és konfiguráció.
Az `attribute_slug` az egyetlen kapocs a `MaterialBasePrice` és a termék attribútumai között.

---

## Nyitott kérdések

- [ ] Milyen slug-okkal vannak a mennyiségi attribútumok tárolva a `shop_attributes` táblában? (pl. vastagság, liter, kg)
- [ ] A rendszer termékeknél a komponensek `quantity` értéke fix (mindig ugyanannyi), vagy termékenként eltérő?
- [ ] Kell-e audit log az alapár változásokhoz? (ki mikor mit írt át)
