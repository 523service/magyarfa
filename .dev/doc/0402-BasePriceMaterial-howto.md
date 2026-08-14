# Dinamikus Alapár Rendszer – Kezelési útmutató

**Érintett terület:** Admin panel → Products → Alapárak
**Dátum:** 2026-04-02

---

## Mi ez és mire való?

Az anyagárak (hőszigetelő lapok, ragasztók, hálók stb.) naponta változnak.
Ez a rendszer lehetővé teszi, hogy **egyetlen helyen frissítsd az alapárat**, és az összes érintett termék ára automatikusan újraszámolódik — a termékeket nem kell egyenként szerkeszteni.

---

## Az ár-kiszámítás logikája (prioritási sorrend)

```
1. Termék ára > 0          → a manuálisan beállított ár érvényes (felülír mindent)
2. Termék ára = 0 és van termékszintű komponens  → Σ(komponens.egységár × fix_mennyiség)
3. Termék ára = 0 és az alapárnak van receptje   → Σ(összetevő.egységár × mennyiség*)
4. Termék ára = 0 és az alapár egyszerű          → alapár.egységár × termék_attribútum_értéke
5. Semmi sem illeszkedik  → 0 Ft
```

> *A mennyiség lehet **fix szám** (pl. 3.5 kg ragasztó) vagy **termék attribútumból** olvasott érték (pl. `vastagsag` attribútum értéke cm-ben).

---

## Háromféle terméktípus

### 1. Manuális árú termék
A termék `Eladási ár` mezője ki van töltve (> 0).
→ Ez az ár jelenik meg, az alapár rendszer nem lép életbe.
→ Használd: kész termékek, ahol az ár nem anyagarányos.

---

### 2. Egyszerű dinamikus termék
A termék `Eladási ár` = 0, és be van állítva egy **Dinamikus alapár** (pl. „EPS Standard").
A számítás: `egységár × termék_attribútum_értéke`

**Példa:** EPS 10 cm lap
- Alapár: „EPS Standard" → 282 Ft/cm
- Attribútum slug az alapáron: `vastagsag`
- A termék `vastagsag` attribútuma: 10
- **Ár: 282 × 10 = 2 820 Ft/m²**

Ha másnap az EPS ára 290 Ft/cm-re változik → csak az alapárat frissítsd → minden EPS termék ára automatikusan frissül.

---

### 3. Rendszer termék (recept alapú)
A termék `Eladási ár` = 0, és be van állítva egy **Dinamikus alapár**, amelyhez **recept összetevők** vannak definiálva.
A számítás: az összetevők árának összege.

**Példa:** „EPS Standard Rendszer" csomag – 10 cm-es változat

| Összetevő        | Anyag alapár              | Mennyiség        | Sor ár         |
|------------------|---------------------------|------------------|----------------|
| EPS lemez        | EPS Standard (282 Ft/cm)  | `vastagsag` attrib. (= 10) | 2 820 Ft |
| Ragasztó         | Ragasztó alap (150 Ft/kg) | 3.5 kg (fix)     | 525 Ft         |
| Üvegszövet háló  | Háló alap (180 Ft/m²)     | 4.5 m² (fix)     | 810 Ft         |
| Felületi bevonó  | Bevonó alap (200 Ft/kg)   | 2.0 kg (fix)     | 400 Ft         |
| **ÖSSZESEN**     |                           |                  | **4 555 Ft**   |

Minden „EPS Standard Rendszer" termékre (5 cm, 10 cm, 15 cm stb.) **ugyanazt a sablont** kell beállítani. Az EPS sor automatikusan a termék `vastagsag` attribútumát használja mennyiségként — tehát az 5 cm-es változat ára automatikusan más lesz mint a 10 cm-esé.

---

## Lépésről lépésre: hogyan állíts be egy rendszer terméket?

### 1. lépés – Anyag alapárak felvétele

Admin → Products → Alapárak → **Új alapár**

Vegyél fel minden anyagot külön-külön:

| Megnevezés       | Egységár | Attrib. slug | Egységcímke |
|------------------|----------|--------------|-------------|
| EPS Standard     | 282      | vastagsag    | cm          |
| Grafitos EPS     | 340      | vastagsag    | cm          |
| Kőzetgyapot      | 420      | vastagsag    | cm          |
| Ragasztó alap    | 150      | —            | kg          |
| Üvegszövet háló  | 180      | —            | m2          |
| Felületi bevonó  | 200      | —            | kg          |

> Az **Attribútum slug** az egyszerű termékeknél fontos — melyik termék attribútumból olvassa a mennyiséget.
> A recept összetevőknél az attribútum slug az összetevő szintjén van beállítva (nem az alapáron).

---

### 2. lépés – Recept definiálása (rendszer termékhez)

Ha a termék egy **rendszer csomag** (EPS + ragasztó + háló + bevonó együtt), a receptet az alapárhoz kell felvenni:

1. Admin → Products → Alapárak → válaszd ki pl. „EPS Standard Rendszer"-t (vagy hozz létre újat)
2. Az oldal alján a **„Recept összetevők"** táblában kattints az **Új** gombra
3. Töltsd ki minden sorhoz:
   - **Anyag alapár**: melyik alapárból jön az egységár (pl. „EPS Standard")
   - **Megnevezés**: pl. „EPS lemez" (csak megjelenítési célra)
   - **Fix mennyiség**: ha az összetevő mennyisége mindig ugyanannyi (pl. ragasztóból 3.5 kg)
   - **Attribútum slug**: ha a mennyiség a termékről jön (pl. `vastagsag` — az EPS-nél)
   - **Sorrend**: megjelenítési sorrend

> ⚠️ Egy összetevőnél vagy a **Fix mennyiség** VAGY az **Attribútum slug** legyen kitöltve — a kettő közül az attribútum slug élvez prioritást.

---

### 3. lépés – Termék beállítása

1. Admin → Products → Products → válaszd ki a terméket
2. **Árazás** szekció:
   - `Eladási ár`: legyen **0** (ez jelzi, hogy dinamikus számítást kér)
   - `Dinamikus alapár`: válaszd ki a megfelelő alapárat (pl. „EPS Standard Rendszer")
3. Győződj meg róla, hogy a termékhez be van állítva a szükséges **attribútum** (pl. `vastagsag = 10`)

---

### 4. lépés – Napi árfrissítés

Ha az anyagár változik:

1. Admin → Products → Alapárak
2. Keresd meg az érintett alapárat (pl. „EPS Standard")
3. Módosítsd az **Egységár** mezőt
4. Mentés → **az összes termék ára automatikusan frissül**, nincs más teendő

---

## Speciális eset: termékszintű felülírás

Ha egy konkrét terméknek **egyedi receptje** van (eltér az alapár sablonjától), azt a termék szerkesztő oldalán az **„Ár komponensek"** táblában lehet beállítani.

> ⚠️ Ha egy terméknek van termékszintű komponense, az **felülírja** az alapár receptjét — a sablon figyelmen kívül marad.
> Ez az esetek <5%-ában szükséges; általában az alapár sablon elég.

---

## Összefoglalás

```
Ár változtatáshoz:
  Alapárak → Egységár módosítása → kész ✓

Új egyszerű termékhez:
  Termék → Eladási ár = 0 + Dinamikus alapár kiválasztása → kész ✓

Új rendszer termékhez:
  1. Alapár létrehozása (ha még nincs)
  2. Recept összetevők felvétele az alapárhoz
  3. Termék → Eladási ár = 0 + Dinamikus alapár = a sablon → kész ✓
```
