# Magyar Fa weboldal – Laravel + Livewire + Filament fejlesztési terv

## 1. Projektcél

Modern, gyors, mobilbarát faanyag-kereskedelmi weboldal létrehozása, amely:

- bemutatja a Magyar Fa termékkínálatát
- publikus árakat jelenít meg
- kereshető és szűrhető termékkatalógust biztosít
- nem klasszikus webshopként működik
- ajánlatkérési listába engedi gyűjteni a termékeket
- strukturált ajánlatkérést küld be
- Filament adminfelületen kezelhető
- később könnyen bővíthető rendelési vagy készletkezelési funkciókkal

---

## 2. Technológiai stack

### Backend

- Laravel 12
- PHP 8.3 vagy 8.4
- MySQL 8 / MariaDB 10.6+
- Filament v4
- Livewire v3
- Laravel Queues
- Laravel Notifications
- Laravel Scout opcionálisan

### Frontend

- Blade
- Livewire
- Alpine.js
- Tailwind CSS
- Vite
- Lucide vagy Heroicons ikonok

### Ajánlott kiegészítők

- Spatie Media Library
- Spatie Laravel Permission
- Spatie Sluggable
- Spatie Activitylog
- Laravel Sitemap
- Laravel Backup
- Laravel Honeypot vagy Turnstile

---

## 3. Fő oldalak

### Publikus oldalak

1. Kezdőlap
2. Termékek
3. Termékkategória
4. Termék részletező
5. Ajánlatkérési lista
6. Ajánlatkérési űrlap
7. Ajánlatkérés sikeres
8. Rólunk
9. Szolgáltatások
10. Kapcsolat
11. Adatkezelési tájékoztató
12. Impresszum
13. ÁSZF vagy ajánlatkérési feltételek

### Opcionális második fázis

- blog / tudástár
- építőipari kalkulátorok
- letölthető árlista
- gyakori kérdések
- referencia projektek

---

## 4. Adatmodell

## 4.1 Category

```text
id
parent_id nullable
name
slug
short_description nullable
description nullable
image_id nullable
icon nullable
sort_order
is_active
seo_title nullable
seo_description nullable
created_at
updated_at
```

Hierarchikus kategóriák támogatása szükséges.

---

## 4.2 Product

```text
id
category_id
name
slug
sku nullable
short_description nullable
description nullable
unit
price nullable
price_type
vat_rate
is_gross_price
stock_status
lead_time nullable
is_featured
is_active
sort_order
seo_title nullable
seo_description nullable
created_at
updated_at
```

### Javasolt `price_type` értékek

- fixed
- from
- quote_only

### Javasolt `unit` értékek

Ne adatbázis-enumként legyenek kezelve.

Példák:

- db
- fm
- m²
- m³
- csomag
- raklap

---

## 4.3 ProductVariant

Szükséges, ha egy termék több méretben vagy kivitelben létezik.

```text
id
product_id
name
sku nullable
price nullable
unit
length nullable
width nullable
height nullable
thickness nullable
quality_class nullable
stock_status
sort_order
is_active
created_at
updated_at
```

Példa:

```text
Gerenda
- 50×100 mm
- 75×150 mm
- 100×100 mm
```

---

## 4.4 ProductAttribute

```text
id
name
slug
type
unit nullable
is_filterable
sort_order
```

## 4.5 ProductAttributeValue

```text
id
product_id nullable
product_variant_id nullable
attribute_id
value
```

Lehetséges attribútumok:

- szélesség
- magasság
- vastagság
- hosszúság
- minőségi osztály
- fafajta
- felületkezelés
- nútféderes
- felhasználási terület

---

## 4.6 QuoteRequest

```text
id
quote_number
customer_type
company_name nullable
tax_number nullable
name
email
phone
postcode nullable
city nullable
address nullable
message nullable
status
source nullable
privacy_accepted_at
submitted_at
created_at
updated_at
```

### Javasolt státuszok

- new
- processing
- clarification_needed
- quoted
- won
- lost
- archived

A státuszok konfigurációs táblában vagy PHP enumként is kezelhetők. Üzletileg ritkán változnak, ezért itt az enum elfogadható.

---

## 4.7 QuoteRequestItem

```text
id
quote_request_id
product_id nullable
product_variant_id nullable
product_name
variant_name nullable
sku nullable
quantity
unit
displayed_price nullable
note nullable
created_at
updated_at
```

A termékadatokat snapshotként is el kell menteni, hogy egy későbbi termékmódosítás ne írja át a korábbi ajánlatkérés tartalmát.

---

## 4.8 SiteSetting

```text
id
key
value
type
group
```

Kezelendő adatok:

- cím
- telefonszám
- email
- nyitvatartás
- közösségi linkek
- ajánlatkérési email cím
- alapértelmezett SEO adatok
- céges adatok

---

## 5. Livewire komponensek

### Termékoldal

```text
ProductCatalog
ProductSearch
ProductFilters
ProductSort
ProductViewToggle
ProductList
ProductCard
ProductPagination
MobileFilterDrawer
```

### Ajánlatkérési lista

```text
QuoteCart
QuoteCartButton
QuoteCartDrawer
QuoteCartPage
QuoteCartItem
QuoteRequestForm
```

### Egyéb

```text
ContactForm
NewsletterForm
CategoryNavigation
HeaderSearch
```

---

## 6. Termékkatalógus működése

### Szűrés

- kategória
- alkategória
- árintervallum
- mértékegység
- készletállapot
- termékjellemzők
- kiemelt termékek

### Keresés

Első verzióban:

- terméknév
- SKU
- rövid leírás
- attribútumértékek

Később:

- Laravel Scout
- Meilisearch vagy Typesense
- elgépelést toleráló keresés
- keresési javaslatok

### Rendezés

- népszerűség
- név A–Z
- név Z–A
- ár növekvő
- ár csökkenő
- legújabb

### URL állapot

A szűrők kerüljenek query stringbe.

Példa:

```text
/termekek?category=fenyo-fureszaru&search=gerenda&sort=price-asc
```

Előnye:

- megosztható
- visszalépésnél megmarad
- SEO és analitika szempontból jobb
- Livewire-rel jól kezelhető

---

## 7. Ajánlatkérési lista

### Működés

A felhasználó termékeket adhat az ajánlatkérési listához.

Minden tételnél megadható:

- mennyiség
- mértékegység
- választott változat
- egyedi megjegyzés
- kért méret
- kért hosszúság
- szállítási igény

### Tárolás

Vendég felhasználóknál:

- session
- opcionálisan localStorage szinkron

Bejelentkezett felhasználó későbbi fázisban:

- adatbázis
- mentett ajánlatkérések
- ismételt ajánlatkérés

### UX

- fejlécben darabszám
- mobilon fix alsó ikon
- hozzáadás után toast visszajelzés
- drawerben gyors áttekintés
- külön oldalon részletes szerkesztés

---

## 8. Ajánlatkérési folyamat

### Lépések

1. termékek kiválasztása
2. mennyiségek pontosítása
3. kapcsolattartási adatok
4. szállítási vagy átvételi igény
5. megjegyzés
6. adatkezelés elfogadása
7. beküldés
8. visszaigazoló oldal
9. email ügyfélnek
10. email / értesítés adminnak

### Ajánlatkérési szám

Formátum:

```text
MF-2026-000123
```

### Email tartalma

- ajánlatkérés azonosító
- ügyfél adatai
- tételek
- mennyiségek
- megjegyzések
- beküldési idő
- admin link az ajánlatkéréshez

---

## 9. Filament admin

## 9.1 Dashboard

Widgetek:

- új ajánlatkérések
- feldolgozás alatt
- heti ajánlatkérések
- leggyakrabban kért termékek
- becsült ajánlatérték
- nyitott feladatok

---

## 9.2 CategoryResource

Funkciók:

- hierarchikus kategóriakezelés
- sorrendezés
- kép feltöltése
- SEO mezők
- aktív/inaktív állapot
- drag and drop sorrend

---

## 9.3 ProductResource

Funkciók:

- alapadatok
- kategória
- képek
- ár
- mértékegység
- ártípus
- készletállapot
- változatok
- attribútumok
- kiemelés
- SEO
- tömeges aktiválás
- tömeges ármódosítás

---

## 9.4 QuoteRequestResource

Funkciók:

- ajánlatkérések listázása
- státuszkezelés
- ügyféladatok
- tételek
- belső megjegyzés
- email küldés
- nyomtatható nézet
- PDF export később
- tevékenységnapló

---

## 9.5 SiteSettings

Kezelhető adatok:

- cégadatok
- kapcsolat
- fejléc szövegek
- kezdőoldali blokkok
- nyitvatartás
- közösségi linkek
- email címzettek
- alap SEO beállítások

---

## 10. Kezdőoldal szekciók

1. hero banner
2. fő CTA-k
3. előnyök
4. fő termékkategóriák
5. népszerű termékek
6. ajánlatkérési folyamat röviden
7. fatelep bemutatása
8. szolgáltatások
9. kapcsolat és térkép
10. footer

---

## 11. Termékoldal felépítése

### Desktop

- hero / breadcrumb
- felső kereső és rendezősáv
- bal oldali kategória- és szűrőpanel
- jobb oldali lista vagy grid
- lapozás
- szolgáltatási előnyök
- kapcsolat CTA

### Mobil

- kompakt hero
- kereső
- szűrő gomb
- rendezés select
- összecsukható kategóriák
- egyoszlopos terméklista
- sticky alsó navigáció

---

## 12. SEO

### Technikai SEO

- szemantikus HTML
- canonical URL
- sitemap.xml
- robots.txt
- Open Graph
- Twitter Card
- strukturált adatok
- gyors oldalbetöltés
- optimalizált képek

### Strukturált adatok

- LocalBusiness
- Organization
- Product
- BreadcrumbList
- FAQPage

### Lokális SEO

Fő kulcsszavak:

- fatelep Csömör
- faanyag Csömör
- fűrészáru Csömör
- tetőszerkezeti faanyag
- gerenda ár
- OSB lemez Csömör
- gyalult deszka
- zsaludeszka

---

## 13. Teljesítmény

### Követelmények

- WebP / AVIF képek
- lazy loading
- responsive image srcset
- eager loading csak hero képre
- DB indexek
- Livewire query optimalizálás
- N+1 lekérdezések kerülése
- cache kategóriákra és beállításokra
- queue email küldéshez

### Célértékek

- Lighthouse Performance: 90+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 95+
- LCP: 2,5 s alatt
- CLS: 0,1 alatt

---

## 14. Biztonság

- CSRF védelem
- rate limit ajánlatkérésre
- honeypot vagy Cloudflare Turnstile
- admin 2FA
- jogosultságkezelés
- fájlfeltöltések validálása
- audit log
- biztonságos header beállítások
- rendszeres backup

---

## 15. Jogosultságok

### Admin

- teljes hozzáférés

### Értékesítő

- ajánlatkérések kezelése
- termékadatok megtekintése
- belső megjegyzés
- státuszváltás

### Tartalomkezelő

- termékek
- kategóriák
- oldalak
- képek
- SEO

---

## 16. Analitika

Javasolt:

- Google Analytics 4 vagy Plausible
- Google Search Console
- ajánlatkérési konverzió
- termék hozzáadása ajánlatkéréshez
- ajánlatkérés megkezdése
- ajánlatkérés beküldése
- telefonkattintás
- emailkattintás
- térképkattintás

Fontos események:

```text
view_product
search_products
filter_products
add_to_quote
view_quote
begin_quote_request
submit_quote_request
click_phone
click_email
```

---

## 17. Fejlesztési fázisok

## Fázis 1 – Alapok

- Laravel projekt
- autentikáció
- Filament telepítés
- Tailwind és design tokenek
- közös layoutok
- alap komponensek
- beállításkezelés

**Becsült idő:** 2–3 nap

---

## Fázis 2 – Termékkatalógus backend

- kategóriák
- termékek
- változatok
- attribútumok
- képek
- árak
- Filament resource-ok

**Becsült idő:** 4–6 nap

---

## Fázis 3 – Publikus frontend

- kezdőlap
- kategóriaoldalak
- terméklista
- termékrészletező
- reszponzív fejléc és footer
- design implementáció

**Becsült idő:** 5–7 nap

---

## Fázis 4 – Keresés, szűrés, rendezés

- Livewire katalógus
- query string szinkron
- mobil szűrő drawer
- lista/grid nézet
- lapozás

**Becsült idő:** 3–5 nap

---

## Fázis 5 – Ajánlatkérési lista

- session alapú lista
- hozzáadás és törlés
- mennyiségek
- változatok
- drawer
- ajánlatkérési oldal

**Becsült idő:** 3–4 nap

---

## Fázis 6 – Ajánlatbeküldés és admin kezelés

- ajánlatkérési űrlap
- adatmentés
- email értesítések
- Filament QuoteRequestResource
- státuszkezelés
- activity log

**Becsült idő:** 3–5 nap

---

## Fázis 7 – Tartalom, SEO, tesztelés

- tartalmak feltöltése
- meta adatok
- schema markup
- teljesítményoptimalizálás
- mobilteszt
- böngészőteszt
- hibajavítás

**Becsült idő:** 3–5 nap

---

## 18. Teljes MVP becslés

### Fejlesztési idő

**23–35 munkanap**

Reális egyéni fejlesztői ütemezéssel:

**5–7 hét**

### Nem része az alap MVP-nek

- online fizetés
- készletkezelő integráció
- automatikus szállítási díj
- ERP kapcsolat
- ügyfélfiók
- rendeléskezelés
- számlázás
- automatikus ajánlatgenerálás
- több telephely

---

## 19. Tesztelési terv

### Automatizált tesztek

- kategóriaoldal
- termékszűrés
- keresés
- ajánlatkérési listához adás
- mennyiségmódosítás
- ajánlatbeküldés
- email értesítés
- admin státuszváltás
- jogosultságok

### Manuális tesztek

- Chrome
- Firefox
- Edge
- Safari
- Android Chrome
- iPhone Safari
- tablet nézet
- lassú mobilhálózat

---

## 20. Deployment

### Környezetek

- local
- staging
- production

### Javasolt infrastruktúra

- VPS
- Nginx
- PHP-FPM
- MySQL
- Redis
- Supervisor
- cron
- SSL
- automatikus backup

### CI/CD

- GitHub Actions
- tesztek futtatása
- statikus elemzés
- build
- staging deploy
- manuális production deploy

---

## 21. Kódstruktúra

```text
app/
├── Filament/
├── Livewire/
│   ├── Catalog/
│   ├── Quote/
│   └── Shared/
├── Models/
├── Services/
│   ├── CatalogService.php
│   ├── QuoteCartService.php
│   └── QuoteRequestService.php
├── Actions/
├── DTOs/
├── Enums/
└── Notifications/

resources/
├── views/
│   ├── layouts/
│   ├── components/
│   ├── livewire/
│   └── pages/
├── css/
└── js/
```

---

## 22. Fejlesztési alapelvek

- a termékkatalógus legyen önálló domain
- az ajánlatkérési logika ne közvetlenül a Livewire komponensben legyen
- üzleti logika service vagy action osztályokba kerüljön
- Livewire komponensek maradjanak vékonyak
- ár és termék snapshot készüljön ajánlatbeküldéskor
- szűrők query stringben működjenek
- komponensek újrahasznosíthatók legyenek
- a mobil UX ne desktop layout összenyomása legyen
- minden fontos művelethez legyen teszt

---

## 23. Első fejlesztési sprint javaslat

### Sprint 1

- projekt inicializálás
- Filament
- design rendszer
- kategória és termék migrációk
- admin CRUD
- médiafeltöltés
- kezdőoldali layout

### Sprint 2

- termékkatalógus
- keresés
- szűrés
- rendezés
- mobil nézet

### Sprint 3

- ajánlatkérési lista
- ajánlatbeküldés
- email
- admin workflow

### Sprint 4

- tartalom
- SEO
- tesztelés
- optimalizálás
- élesítés
