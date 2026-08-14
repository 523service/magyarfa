# Árazás – Lépésről lépésre (Kezelői kézikönyv)

**Kinek szól:** adminisztrátorok, kezelők  
**Admin belépés:** `/admin`  
**Dátum:** 2026-04-08

---

## Mielőtt bármibe kezdesz – értsd meg a három módot

| Mód | Mikor használd | Ár forrása |
|---|---|---|
| **Manuális** | Kész termék, fix ár (pl. szerszám, kiegészítő) | Te adod meg kézzel |
| **Képlet** | Hőszigetelő LAP, ahol ár = anyag × vastagság | Automatikus számítás |
| **Rendszer sablon** | Teljes rendszer (lap + ragasztó + háló együtt) | Automatikus számítás |

> **Arany szabály:** ha az anyagár változik, csak az Alapáraknál kell átírni – minden termék ára automatikusan frissül.

---

## 1. MANUÁLIS ÁR – lépésről lépésre

### Mikor használd?
Szerszámok, kiegészítők, egyedi termékek, ahol az ár nem függ anyagáraktól.

### Példa: „Hőszigetelő rögzítőcsavar készlet" → ára 4 990 Ft

**Lépések:**

1. Menj: **Admin → Products → Products**
2. Keresd meg a terméket, kattints **Szerkesztés**
3. Görgess az **Árazás** szekcióhoz
4. **Árazási mód** mezőből válaszd: `Manuális`
5. Megjelenik az **Eladási ár (br.)** mező → írd be: `4990`
6. Kattints **Mentés**

```
Eredmény: a termék ára = 4 990 Ft (pont amit beírtál)
```

### Ár módosítása később:
Ugyanott, az **Eladási ár (br.)** mezőt átírod → Mentés. Kész.

---

## 2. KÉPLET ÁR – lépésről lépésre

### Mikor használd?
Hőszigetelő **lapok**, ahol: `ár = anyag egységára × vastagság`

### Példa: „EPS 80 hőszigetelő lap 10 cm" → ára 282 × 10 = **2 820 Ft/m²**

---

### 2/A lépés: Alapár létrehozása (csak egyszer kell!)

Ha az anyag alapára még nem létezik a rendszerben:

1. Menj: **Admin → Products → Alapárak**
2. Kattints **Új alapár**
3. Töltsd ki:

| Mező | Mit írj be | Példa |
|---|---|---|
| **Megnevezés** | Az anyag neve | `EPS 80 lap` |
| **Egységár (Ft)** | Aktuális ár per egység | `282` |
| **Egységcímke** | Mihez viszonyítod | `cm` |
| **Aktív** | Legyen bekapcsolva | ✓ |

4. Kattints **Mentés**

> **Fontos:** ezt az alapárat minden hasonló termék (10 cm, 15 cm, 20 cm) közösen használja. Csak egyszer kell felvenni!

---

### 2/B lépés: Termék beállítása

1. Menj: **Admin → Products → Products**
2. Keresd meg az „EPS 80 hőszigetelő lap 10 cm" terméket → **Szerkesztés**
3. Görgess az **Árazás** szekcióhoz
4. **Árazási mód** → válaszd: `Képlet (anyag × vastagság)`
5. Megjelennek az új mezők:

| Mező | Mit válassz/írj | Példa |
|---|---|---|
| **Képlet típusa** | Lap ára: egységár × vastagság (cm) | ezt válaszd |
| **Anyag alapár** | Keresd rá az imént létrehozottat | `EPS 80 lap (282 Ft/cm)` |
| **Vastagság (cm)** | A termék vastagsága | `10` |

6. Kattints **Mentés**

```
Eredmény: 282 × 10 = 2 820 Ft/m² — automatikusan számolva
```

### Új vastagság (15 cm) felvétele ugyanabból az anyagból:

Ugyanúgy mint fent, de a **Vastagság (cm)** mezőbe `15`-öt írj.  
Alapárat NEM kell újra felvenni. → 282 × 15 = **4 230 Ft/m²**

---

## 3. RENDSZER SABLON ÁR – lépésről lépésre

### Mikor használd?
Teljes hőszigetelő **rendszerek**, ahol több anyag együtt alkotja a terméket  
(EPS lap + ragasztótapasz + üvegszövetháló stb.)

### Példa: „EPS 80 hőszigetelő alaprendszer 8 cm"

| Összetevő | Egységár | Mennyiség | Sor összeg |
|---|---|---|---|
| EPS 80 lap | 282 Ft/cm | 8 cm (vastagság) | 2 256 Ft |
| TDR40 ragasztótapasz | 450 Ft/m² | 1 (fix) | 450 Ft |
| Üvegszövetháló | 380 Ft/m² | 1 (fix) | 380 Ft |
| **Végeredmény** | | | **3 086 Ft/m²** |

---

### 3/A lépés: Alapárak létrehozása (egyszer kell minden anyaghoz)

Minden összetevőhöz külön alapárat kell felvenni ha még nincs:

**Admin → Products → Alapárak → Új alapár** – háromszor:

**1. EPS 80 lap:**
- Megnevezés: `EPS 80 lap`
- Egységár: `282`
- Egységcímke: `cm`

**2. TDR40 ragasztótapasz:**
- Megnevezés: `TDR40 ragasztótapasz`
- Egységár: `450`
- Egységcímke: `m²`

**3. Üvegszövetháló:**
- Megnevezés: `Üvegszövetháló`
- Egységár: `380`
- Egységcímke: `m²`

---

### 3/B lépés: Rendszer sablon létrehozása (csak egyszer kell a sablonhoz!)

1. Menj: **Admin → Products → Rendszer sablonok**
2. Kattints **Új sablon**
3. Töltsd ki:
   - **Megnevezés:** `EPS Standard Rendszer`
   - **Aktív:** bekapcsolva ✓
4. Kattints **Mentés**
5. Megnyílik a szerkesztő oldal. Kattints az **Összetevők** fülre
6. Kattints **Új összetevő** – háromszor:

**1. összetevő – EPS 80 lap (vastagság-függő):**

| Mező | Mit tölts ki |
|---|---|
| Anyag alapár | `EPS 80 lap (282 Ft/cm)` |
| Megnevezés | `EPS 80 lap` |
| Mennyiség típusa | `Termék vastagság (cm)` |
| Sorrend | `1` |

**2. összetevő – Ragasztótapasz (fix):**

| Mező | Mit tölts ki |
|---|---|
| Anyag alapár | `TDR40 ragasztótapasz (450 Ft/m²)` |
| Megnevezés | `TDR40 ragasztótapasz` |
| Mennyiség típusa | `Fix mennyiség` |
| Fix mennyiség értéke | `1` |
| Sorrend | `2` |

**3. összetevő – Üvegszövetháló (fix):**

| Mező | Mit tölts ki |
|---|---|
| Anyag alapár | `Üvegszövetháló (380 Ft/m²)` |
| Megnevezés | `Üvegszövetháló` |
| Mennyiség típusa | `Fix mennyiség` |
| Fix mennyiség értéke | `1` |
| Sorrend | `3` |

> **Kész!** A sablont csak egyszer kell beállítani. A 8 cm, 10 cm, 12 cm stb. termékek mind ugyanezt a sablont használják.

---

### 3/C lépés: Termék beállítása

1. Menj: **Admin → Products → Products**
2. Keresd meg: „EPS 80 hőszigetelő alaprendszer 8 cm" → **Szerkesztés**
3. Görgess az **Árazás** szekcióhoz
4. **Árazási mód** → válaszd: `Rendszer sablon`
5. Megjelennek az új mezők:

| Mező | Mit válassz/írj |
|---|---|
| **Rendszer sablon** | `EPS Standard Rendszer` |
| **Vastagság (cm)** | `8` |

6. Kattints **Mentés**

```
Eredmény: (282 × 8) + (450 × 1) + (380 × 1) = 3 086 Ft/m² — automatikusan
```

### Ugyanez 10 cm-es termékre:
Pontosan ugyanígy, **Vastagság (cm)** = `10` → (282 × 10) + 450 + 380 = **3 650 Ft/m²**

### Ugyanez 15 cm-es termékre:
**Vastagság (cm)** = `15` → (282 × 15) + 450 + 380 = **5 060 Ft/m²**

> Sablont és alapárakat NEM kell újra felvenni. Csak a vastagságot kell átírni.

---

## Napi árfrissítés – ha az anyagár változik

**Helyzet:** Az EPS 80 ára 282 Ft/cm-ről 295 Ft/cm-re változik.

**Mit kell csinálni:**

1. Menj: **Admin → Products → Alapárak**
2. Keresd meg: `EPS 80 lap`
3. Kattints **Szerkesztés**
4. **Egységár (Ft)** mezőt írd át: `295`
5. Kattints **Mentés**

**Kész.** A rendszer automatikusan újraszámolja:
- Minden EPS 80 lap terméket (Képlet mód)
- Minden EPS rendszer terméket (Rendszer sablon mód)

Nem kell egyenként megnyitni a termékeket.

---

## Összefoglaló – melyik módot mikor?

```
Hőszigetelő LAP (pl. EPS 80 10 cm lap)
  → Képlet mód
  → Anyag alapár: EPS 80 lap
  → Vastagság: 10 cm

Teljes RENDSZER (pl. EPS 80 alaprendszer 10 cm)
  → Rendszer sablon mód
  → Sablon: EPS Standard Rendszer
  → Vastagság: 10 cm

Kiegészítő, szerszám, fix árú termék
  → Manuális mód
  → Eladási ár: kézzel beírva

Ha az anyagár változik:
  → Csak Alapárak → Egységár átírása → mentés
  → Minden termék ára automatikusan frissül
```

---

## Gyakori hibák

| Hiba | Ok | Megoldás |
|---|---|---|
| A termék ára 0 Ft | Nincs kiválasztva Anyag alapár vagy Rendszer sablon | Szerkesztés → Árazás szekció → ellenőrizd a mezőket |
| A termék ára nem változott az alapár módosítása után | Lehet néhány másodperc késés | Várj egy kicsit, majd frissítsd az oldalt |
| Nem látom az Egységár mezőt | Rossz Árazási mód van kiválasztva | Árazási mód = Manuális esetén jelenik meg |
| Nem látom a Vastagság mezőt | Árazási mód nincs Képlet vagy Rendszer sablon értékre állítva | Ellenőrizd az Árazási mód legördülőt |
