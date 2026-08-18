# Magyar Fa – Design Style Guide

## 1. Márkaalapok

**Márkanév:** Magyar Fa  
**Pozicionálás:** helyi, megbízható faanyag-kereskedés Csömörön  
**Fő üzenet:** minőségi faanyag, átlátható árak, személyes kiszolgálás  
**Hangnem:** szakértő, közvetlen, korrekt, bizalomépítő  
**Vizuális irány:** prémium fatelep, természetes anyagok, modern kereskedelmi megjelenés

A design ne legyen sem barkácsáruházas, sem klasszikus webshopos. Az oldal elsődleges célja az ajánlatkérés támogatása.

---

## 2. Színpaletta

### Elsődleges színek

| Szerep | Szín | HEX |
|---|---|---|
| Elsődleges sötétzöld | fejléc, CTA, footer | `#12351F` |
| Mély erdőzöld | hover, kiemelt blokkok | `#0B2918` |
| Aranybarna | elsődleges akcentus | `#B98432` |
| Világos arany | hover, ikonok | `#D1A254` |

### Háttér- és semleges színek

| Szerep | Szín | HEX |
|---|---|---|
| Törtfehér háttér | fő oldalháttér | `#F7F3EA` |
| Világos bézs | kártyák, szekciók | `#EEE5D5` |
| Fehér | tartalomkártyák | `#FFFFFF` |
| Sötét szöveg | fő szöveg | `#182019` |
| Középszürke | másodlagos szöveg | `#667067` |
| Szegély | input, kártya | `#D8D0C3` |

### Állapotszínek

| Állapot | HEX |
|---|---|
| Készleten | `#2E7D32` |
| Figyelmeztetés | `#C47A1A` |
| Hiba | `#B3261E` |
| Információ | `#2D5F73` |

---

## 3. Tipográfia

### Betűtípus-javaslat

**Címsorok:** `Barlow Condensed`, fallback: `Arial Narrow`, sans-serif  
**Törzsszöveg és UI:** `Inter`, fallback: `Arial`, sans-serif

### Méretezés

| Elem | Desktop | Mobil | Súly |
|---|---:|---:|---:|
| H1 | 52–64 px | 34–40 px | 700–800 |
| H2 | 34–42 px | 28–32 px | 700 |
| H3 | 24–28 px | 22–24 px | 700 |
| Kártyacím | 18–20 px | 17–18 px | 600–700 |
| Törzsszöveg | 16 px | 15–16 px | 400 |
| Kiegészítő szöveg | 13–14 px | 13px | 400 |
| Gomb | 14–16 px | 14–15 px | 600–700 |
| Ár | 24–30 px | 22–26 px | 700 |

### Szabályok

- A címsorok legyenek tömörek és erősek.
- Nagybetű csak rövid címeknél és navigációs elemeknél használható.
- Hosszú törzsszövegnél maximum 65–75 karakteres sorhossz.
- Árakat mindig jól elkülönítve, egységgel együtt kell megjeleníteni.

---

## 4. Logóhasználat

A Magyar Fa levélmotívumos logója legyen az elsődleges vizuális azonosító.

### Változatok

- teljes logó: ikon + „Magyar Fa” + „Faanyag kereskedés”
- kompakt logó: levélmotívum + márkanév
- mobil ikon: levélmotívum önmagában

### Védőtávolság

A logó körül legalább a logóbetűk magasságának 25%-a maradjon üresen.

### Tiltások

- nem nyújtható aránytalanul
- nem használható mintás háttéren megfelelő kontraszt nélkül
- nem színezhető tetszőlegesen
- nem kerülhet túl közel navigációs vagy CTA elemekhez

---

## 5. Elrendezési rendszer

### Konténer

- maximális szélesség: `1280px`
- desktop oldalsó térköz: `32px`
- tablet: `24px`
- mobil: `16px`

### Grid

- desktop: 12 oszlop
- tablet: 8 oszlop
- mobil: 4 oszlop

### Szekciótávolság

| Méret | Desktop | Mobil |
|---|---:|---:|
| Kis | 24 px | 16 px |
| Normál | 48 px | 32 px |
| Nagy | 80 px | 48 px |

---

## 6. Fejléc és navigáció

### Desktop

1. felső információs sáv:
   - cím
   - telefonszám
   - email
   - ajánlatkérési lista darabszám

2. fő navigáció:
   - logó
   - Kezdőlap
   - Termékek
   - Rólunk
   - Szolgáltatások
   - Kapcsolat
   - Ajánlatkérés CTA

### Mobil

- fix vagy sticky fejléc
- hamburger menü
- középen vagy bal oldalon kompakt logó
- jobb oldalon ajánlatkérési lista ikon darabszámmal
- opcionális alsó mobil navigáció:
  - Kezdőlap
  - Termékek
  - Ajánlatkérés
  - Kapcsolat

---

## 7. Gombok

### Elsődleges CTA

- háttér: aranybarna
- szöveg: fehér
- enyhén lekerekített sarok: `6–8px`
- hover: sötétebb arany / enyhe emelkedés

### Másodlagos CTA

- átlátszó vagy fehér háttér
- sötétzöld keret
- sötétzöld szöveg
- hover: világos bézs háttér

### Sötét CTA

- háttér: sötétzöld
- szöveg: fehér
- használat: ajánlatkéréshez adás, mobil termékkártya

### Gombszabályok

- minimum magasság: `44px`
- mobilon teljes szélesség indokolt esetben
- ikon és szöveg között `8px`
- egy felületen legfeljebb egy domináns elsődleges CTA

---

## 8. Űrlapelemek

### Inputok

- magasság: `44–48px`
- háttér: fehér
- szegély: világosszürke
- fókusz: sötétzöld keret
- lekerekítés: `6px`

### Kereső

- bal oldalon placeholder
- jobb oldalon keresőikon
- mobilon külön ikon-gombos változat használható

### Select

- egységes magasság az inputokkal
- natív select helyett Livewire-kompatibilis egyedi komponens is használható
- rendezésnél egyértelmű opciók:
  - Népszerűség
  - Név szerint
  - Ár szerint növekvő
  - Ár szerint csökkenő

### Szűrőpanelek

- desktopon bal oldali sidebar
- mobilon drawer vagy off-canvas panel
- aktív szűrők címkékkel jelenjenek meg
- legyen „Szűrők törlése” lehetőség

---

## 9. Termékkártyák

### Tartalmi sorrend

1. termékkép
2. terméknév
3. kategória
4. fő méret / specifikáció
5. készletállapot
6. ár és mértékegység
7. „Ajánlatkéréshez adom” gomb
8. részletek link

### Ármegjelenítés

Példák:

- `1 250 Ft / fm`
- `4 690 Ft / db`
- `129 900 Ft / m³`

Az ár mellett jelenjen meg:

- nettó vagy bruttó jelölés
- szükség esetén „-tól”
- árfrissítés dátuma opcionálisan
- egyedi méret esetén „Ajánlat alapján”

### Kép

- arány: `4:3` vagy `1:1`
- világos, egységes háttér
- valódi fatelepi vagy termékfotó előnyben
- minimum 800 px széles forráskép

---

## 10. Kategóriák

### Fő kategóriák

- Fenyő fűrészáru
- Gyalult faáru
- OSB lemez

### Alkategóriák

#### Fenyő fűrészáru

- Komplett tetőszerkezeti faanyag
- Gerenda, palló 6 méterig
- Gerenda, palló 6 méter felett
- Gerenda, palló egyedi méretek
- Tetőléc, zárléc
- Építő deszka
- Zsaludeszka

#### Gyalult faáru

- Lambéria
- Gyalult deszka

#### OSB lemez

- OSB lemez
- Nútféderes OSB lemez

---

## 11. Ikonográfia

- vonalas, egyszerű ikonok
- egységes vonalvastagság
- sötétzöld vagy aranybarna szín
- kerülendők a túl részletes vagy 3D ikonok

Javasolt ikonok:

- teherautó
- minőségi jelvény
- faanyag-rakás
- szakértő / ügyfélkapcsolat
- helyszín
- telefon
- email
- kosár / ajánlatkérési lista

---

## 12. Képi világ

### Preferált

- rendezett fűrészáru-rakatok
- tetőszerkezeti faanyag
- gyalult deszkák közelről
- OSB lapok
- fatelep és kiszolgálás
- természetes fény
- meleg, barnás tónus

### Kerülendő

- túl steril stock fotók
- mesterséges 3D render
- rosszul tárolt faanyag
- túlzsúfolt telepi fotó
- látványosan generált, torz termékkép

---

## 13. Responsive szabályok

### Desktop

- bal oldali kategória- és szűrősáv
- jobb oldali lista vagy grid nézet
- egyszerre több termékadat látható
- sticky filter sidebar használható

### Tablet

- kétoszlopos termékgrid
- összecsukható kategóriapanel
- navigáció egyszerűsítve

### Mobil

- egyoszlopos lista
- nagy érintési felületek
- szűrő off-canvas panelben
- CTA mindig jól elérhető
- ajánlatkérési lista darabszám fixen látható
- termékkártyán csak a legfontosabb adatok jelenjenek meg

---

## 14. Akadálymentesség

- minimum WCAG AA kontraszt
- minden képhez alt szöveg
- minden inputhoz label
- billentyűzettel használható szűrők és menük
- fókuszállapot minden interaktív elemen
- minimum `44×44px` érintési célterület
- ne csak szín jelezze az állapotokat

---

## 15. UI komponenslista

- TopInfoBar
- MainHeader
- MobileHeader
- MobileBottomNav
- HeroBanner
- Breadcrumb
- CategorySidebar
- FilterBar
- SearchInput
- SortSelect
- ActiveFilterBadge
- ProductList
- ProductGrid
- ProductCard
- ProductPrice
- StockBadge
- QuoteListButton
- QuoteListDrawer
- ServiceBenefits
- ContactStrip
- Footer
- EmptyState
- LoadingSkeleton
- Pagination

---

## 16. Design token javaslat

```css
:root {
    --color-primary: #12351F;
    --color-primary-dark: #0B2918;
    --color-accent: #B98432;
    --color-accent-light: #D1A254;

    --color-bg: #F7F3EA;
    --color-surface: #FFFFFF;
    --color-surface-muted: #EEE5D5;

    --color-text: #182019;
    --color-text-muted: #667067;
    --color-border: #D8D0C3;

    --color-success: #2E7D32;
    --color-warning: #C47A1A;
    --color-danger: #B3261E;

    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 14px;

    --shadow-sm: 0 2px 8px rgba(18, 53, 31, 0.08);
    --shadow-md: 0 8px 24px rgba(18, 53, 31, 0.12);

    --container-width: 1280px;
}
```

---

## 17. Márkaélmény

A felület minden pontján ezt kell sugároznia:

- itt valódi szakemberek dolgoznak
- az árak átláthatók
- nem kell webshopban „vakon” rendelni
- az ügyfél összeállíthatja az igényét
- a Magyar Fa visszajelez, pontosít és személyre szabott ajánlatot ad
