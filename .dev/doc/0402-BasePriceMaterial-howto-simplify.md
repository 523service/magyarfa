# Dinamikus Alapár Rendszer – Kezelési útmutató

**Admin panel:** Products → Alapárak
**Dátum:** 2026-04-02

---

## Hogyan működik röviden

Az anyagárat **egy helyen tárolod** (Alapárak). A termékeken csak azt adod meg, hogy **melyik anyagból mennyit** tartalmaz. Az ár automatikusan kiszámolódik.

Ha holnap az EPS ára változik → csak az EPS alapárat írod át → minden termék ára azonnal frissül, semmi mást nem kell csinálni.

---

## Az ár kiszámítása

```
Termék ára > 0          → a manuálisan megadott ár érvényes (felülír mindent)

Termék ára = 0 és van Ár komponens
                        → Σ (anyag egységára × mennyiség)
                           ahol a mennyiség lehet:
                             - fix szám (pl. ragasztónál: 1)
                             - termék attribútumából (pl. vastagság: 10 cm)

Termék ára = 0, egyszerű termék (nincs komponens)
                        → egységár × termék attribútum értéke

Semmi sem illeszkedik   → 0 Ft
```

---

## 1. lépés – Anyag alapárak felvétele

**Admin → Products → Alapárak → Új alapár**

Minden anyagtípust egyszer veszel fel. Például:

| Megnevezés           | Egységár | Egységcímke | Attrib. slug |
|----------------------|----------|-------------|--------------|
| EPS 80 lap           | 282      | cm          | _(üresen hagyható)_ |
| Grafitos EPS lap     | 340      | cm          | _(üresen hagyható)_ |
| Kőzetgyapot lap      | 420      | cm          | _(üresen hagyható)_ |
| TDR40 ragasztótapasz | 450      | m²          | _(üresen hagyható)_ |
| Üvegszövetháló       | 380      | m²          | _(üresen hagyható)_ |
| Felületi bevonó      | 200      | kg          | _(üresen hagyható)_ |

> Az **Egységcímke** csak megjelenítésre szolgál (pl. „282 Ft/cm").
> Az **Attribútum slug** az egyszerű termékeknél kell (ld. lentebb).

---

## 2. lépés – Rendszer termék beállítása

Egy rendszer termék (pl. „EPS 80 alaprendszer 10 cm") több anyagból áll össze.

**Admin → Products → Products → [termék szerkesztése]**

**Árazás szekció:**
- `Eladási ár`: **0** ← ez jelzi, hogy dinamikus számítást kér
- `Dinamikus alapár`: hagyd üresen (rendszer terméknél nem kell)

**Ár komponensek fül** (az oldal alján):

Kattints az **Új** gombra, és vegyél fel minden összetevőt:

### EPS 80 alaprendszer 10 cm – komponensek:

| Anyag alapár                  | Megnevezés           | Fix menny. | Attrib. slug |
|-------------------------------|----------------------|------------|--------------|
| EPS 80 lap (282 Ft/cm)        | EPS 80 lap           | _(üresen)_ | `vastagsag`  |
| TDR40 ragasztótapasz (450 Ft/m²) | TDR40 ragasztótapasz | 1       | _(üresen)_   |
| Üvegszövetháló (380 Ft/m²)    | Üvegszövetháló       | 1          | _(üresen)_   |

**Eredmény (ha a termék `vastagsag` attribútuma = 10):**
```
EPS 80 lap:    282 × 10 = 2 820 Ft
TDR40:         450 × 1  =   450 Ft
Üvegszövetháló: 380 × 1 =   380 Ft
─────────────────────────────────
Összesen:              3 650 Ft/m²
```

### Ugyanez 15 cm-es változatnál:
Ugyanazokat a komponenseket veszed fel, csak a termék `vastagsag` attribútuma lesz 15.
```
EPS 80 lap:    282 × 15 = 4 230 Ft
TDR40:         450 × 1  =   450 Ft
Üvegszövetháló: 380 × 1 =   380 Ft
─────────────────────────────────
Összesen:              5 060 Ft/m²
```

---

## Fix mennyiség vs. Attribútum slug – mikor melyik?

| Eset | Fix menny. | Attrib. slug | Példa |
|------|-----------|--------------|-------|
| Az anyag mennyisége minden változatnál ugyanannyi | ✅ kitöltve (pl. `1`) | üresen | TDR40 ragasztó, üvegszövetháló |
| Az anyag mennyisége a termék attribútumából jön | üresen | ✅ kitöltve (pl. `vastagsag`) | EPS lap, kőzetgyapot lap |

> ⚠️ Egy sorban vagy a **Fix mennyiség** VAGY az **Attribútum slug** legyen kitöltve, nem mindkettő. Ha az attribútum slug ki van töltve, az érvényes.

---

## 3. lépés – Egyszerű dinamikus termék (nem rendszer)

Ha egy termék egyetlen anyagból áll (pl. csak simán EPS lapot adnak el, nem rendszert):

**Árazás szekció:**
- `Eladási ár`: **0**
- `Dinamikus alapár`: válaszd ki pl. „EPS 80 lap"-ot

Az alapáron az **Attribútum slug** mezőbe írd be `vastagsag` → az ár = 282 × termék vastagsága.

**Ár komponenst ilyenkor nem kell felvenni.**

---

## Napi árfrissítés

Ha az EPS ára 282-ről 295-re változik:

1. Admin → Products → Alapárak
2. Keresd meg: „EPS 80 lap"
3. Írd át az **Egységár** mezőt: `295`
4. Mentés

**Kész.** Minden termék ára automatikusan újraszámolódik — sem a termékeket, sem a komponenseket nem kell módosítani.

---

## Összefoglalás

```
Napi árfrissítés:
  Alapárak → Egységár módosítása → kész ✓

Új rendszer termék (EPS + ragasztó + háló):
  Termék → Eladási ár = 0
  Ár komponensek fülön: minden anyag felvétele
    - EPS: attrib. slug = vastagsag (mennyiség automatikus)
    - Ragasztó, háló: fix menny. = 1 → kész ✓

Új egyszerű termék (csak EPS lap):
  Termék → Eladási ár = 0 + Dinamikus alapár = EPS 80 → kész ✓

Manuális áras termék:
  Termék → Eladási ár > 0 → ez érvényes, semmi más nem számít ✓
```
