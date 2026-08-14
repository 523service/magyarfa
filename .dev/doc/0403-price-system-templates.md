# Árazási rendszer – Működési leírás

**Admin panel:** Products → Alapárak / Rendszer sablonok / Products
**Dátum:** 2026-04-03

---

## Hogyan működik röviden

Minden terméknek van egy **árazási módja** (`pricing_mode`). Ez mondja meg egyértelműen, honnan jön az ár. Nincs rejtett fallback lánc, nincs varázslat.

Ha holnap az EPS ára változik → csak az Alapáraknál írod át → minden érintett termék ára automatikusan frissül.

---

## Az három árazási mód

### 1. Manuális (`manual`)

Az admin kézzel adja meg az árat a terméklapon.

```
Termék ára = price mező értéke
```

Mikor használd:
- kész kiegészítők, szerszámok, egyedi termékek
- ahol az ár nem függ anyagáraktól

---

### 2. Képlet (`formula`)

Az ár egyetlen anyagár és egy képlet alapján számolódik.

Jelenleg támogatott képlet típusok:

| `formula_type` | Számítás |
|---|---|
| `board_by_thickness_cm` | egységár × vastagság (cm) |
| `fixed_unit_price` | csak az egységár |

Példa – EPS 80 lap 10 cm:
```
material_price_id → EPS 80 (egységár: 282 Ft/cm)
formula_type      → board_by_thickness_cm
thickness_cm      → 10

Ár = 282 × 10 = 2 820 Ft/m²
```

Mikor használd:
- hőszigetelő lapok, ahol az ár = anyag egységára × vastagság

---

### 3. Rendszer sablon (`system_template`)

Az ár egy sablon alapján számolódik, amely több anyagból épül fel.

```
Ár = Σ (anyag egységára × mennyiség)
```

A mennyiség kétféle lehet:
- **Fix** (`fixed`): mindig ugyanannyi (pl. ragasztó: 1 m² → 1 egység)
- **Termék vastagság** (`product_thickness_cm`): a termék `thickness_cm` mezőjéből jön

Példa – EPS alaprendszer 10 cm:

| Összetevő | Egységár | Menny. típus | Mennyiség | Sor összeg |
|---|---|---|---|---|
| EPS 80 lap | 282 Ft/cm | vastagság | 10 | 2 820 Ft |
| TDR40 ragasztótapasz | 450 Ft/m² | fix | 1 | 450 Ft |
| Üvegszövetháló | 380 Ft/m² | fix | 1 | 380 Ft |
| **Összesen** | | | | **3 650 Ft/m²** |

Ugyanez a sablon 15 cm-es terméknél:

| Összetevő | Egységár | Mennyiség | Sor összeg |
|---|---|---|---|
| EPS 80 lap | 282 Ft/cm | 15 | 4 230 Ft |
| TDR40 ragasztótapasz | 450 Ft/m² | 1 | 450 Ft |
| Üvegszövetháló | 380 Ft/m² | 1 | 380 Ft |
| **Összesen** | | | **5 060 Ft/m²** |

Mikor használd:
- teljes hőszigetelő rendszerek (EPS, grafitos EPS, kőzetgyapot rendszer)
- ahol több anyag együtt alkotja a végső terméket

---

## Beállítás lépései

### 1. Anyag alapárak felvétele

**Admin → Products → Alapárak → Új alapár**

| Mező | Példa | Leírás |
|---|---|---|
| Megnevezés | EPS 80 lap | Csak azonosításra |
| Egységár | 282 | Ft/egység |
| Egységcímke | cm | Megjelenítéshez (pl. „282 Ft/cm") |

> Minden anyagtípust csak egyszer kell felvenni. Nem kell termékenként ismételni.

---

### 2. Rendszer sablon létrehozása

**Admin → Products → Rendszer sablonok → Új sablon**

1. Megnevezés + slug megadása (pl. „EPS Standard Rendszer")
2. Mentés után az **Összetevők** fülön add hozzá a sorokat:
   - Anyag alapár kiválasztása
   - Mennyiség típusa: **Fix** vagy **Termék vastagság (cm)**
   - Ha Fix: add meg a fix mennyiséget (pl. 1)
   - Sorrend beállítása

> A sablont csak egyszer kell beállítani. Minden 10 cm-es, 15 cm-es stb. termék ugyanezt a sablont használja.

---

### 3. Termék beállítása

**Admin → Products → Products → [termék szerkesztése]**

**Árazás szekció:**

| pricing_mode | Szükséges mezők |
|---|---|
| `manual` | Eladási ár |
| `formula` | Képlet típusa + Anyag alapár + Vastagság (cm) |
| `system_template` | Rendszer sablon + Vastagság (cm) |

---

## Napi árfrissítés

Ha az EPS ára 282-ről 295-re változik:

1. **Admin → Products → Alapárak**
2. Megkeresed: „EPS 80 lap"
3. Átírod az **Egységár** mezőt: `295`
4. Mentés

**Kész.** Az összes érintett termék `calculated_price` mezője automatikusan frissül — sem a termékeket, sem a sablonokat nem kell módosítani.

---

## Automatikus újraszámítás

A rendszer automatikusan újraszámolja az érintett termékek árát, ha:

- Anyag alapár egységára változik → minden `formula` és `system_template` termék frissül, amely ezt az anyagot használja
- Rendszer sablon összetevője változik (hozzáadás, módosítás, törlés) → minden termék frissül, amely ezt a sablont használja
- Termék árazási mezői változnak (`pricing_mode`, `formula_type`, `material_price_id`, `system_template_id`, `thickness_cm`)

Az újraszámított ár a `calculated_price` mezőben tárolódik, a frontend ezt használja.

---

## Adatbázis mezők (shop_products)

| Mező | Típus | Leírás |
|---|---|---|
| `pricing_mode` | string | `manual` / `formula` / `system_template` |
| `formula_type` | string nullable | `board_by_thickness_cm` / `fixed_unit_price` |
| `material_price_id` | FK nullable | → `material_base_prices` (formula módhoz) |
| `system_template_id` | FK nullable | → `system_templates` |
| `thickness_cm` | decimal nullable | Vastagság cm-ben |
| `calculated_price` | decimal nullable | Gyorsítótárolt végső ár |
| `price_calculated_at` | timestamp nullable | Utolsó újraszámítás ideje |

---

## Összefoglalás

```
Napi árfrissítés:
  Alapárak → Egységár módosítása → kész ✓

Új rendszer termék (pl. EPS 10 cm):
  pricing_mode = system_template
  system_template_id = EPS Standard Rendszer
  thickness_cm = 10
  → ár automatikusan: (282×10) + (450×1) + (380×1) = 3 650 Ft ✓

Új EPS lap termék (10 cm):
  pricing_mode = formula
  formula_type = board_by_thickness_cm
  material_price_id = EPS 80 (282 Ft/cm)
  thickness_cm = 10
  → ár automatikusan: 282 × 10 = 2 820 Ft ✓

Manuális áras termék:
  pricing_mode = manual
  price = 4 990
  → ez az érvényes ár, semmi más nem számít ✓
```
