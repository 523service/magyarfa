# Competitor Price Monitoring – Implementation Plan (Laravel)

package: https://spatie.be/docs/crawler/v9/introduction

Cél: 500 saját termék árának rendszeres monitorozása két konkurens webáruházban, manuálisan felvitt termék-URL mapping alapján.

## Phase 1 – Projekt keret és alapok

1. Projekt scope és követelmények
    - 2 konkrét konkurens webáruház
    - ~500 saját termék
    - Manuális URL mapping termékenként (A partner URL, B partner URL)
    - Időszakos árlekérdezés (pl. napi 1–4 alkalom)
    - Ár-előzmények tárolása és price change alert

2. Technológiai stack
    - Laravel (aktuális LTS)
    - Adatbázis: MySQL / MariaDB
    - Queue: database / Redis
    - HTTP client: Laravel HTTP client (Guzzle alapú) vagy dedikált crawler csomag
    - Admin UI: Filament / Nova / saját admin

3. Alap konvenciók
    - Minden entitás standard Laravel Eloquent model
    - Soft delete csak ott, ahol indokolt (pl. competitor_product_links)
    - Audit mezők: `created_at`, `updated_at`

---

## Phase 2 – Adatmodell tervezés

Cél: stabil, egyszerű adatmodell, ahol a mapping (saját termék ↔ konkurens URL) külön réteg.

### 2.1. Entitások

1. `products`
    - Saját termékek törzse
    - Mezők (ötlet):
        - `id`
        - `sku` (saját cikkszám)
        - `name`
        - `manufacturer` (opcionális)
        - `own_price` (aktuális saját ár, opcionális)
        - egyéb termékadatok (nem szükséges most implementálni, elég placeholder)

2. `competitors`
    - Konkurens partnerek (A, B)
    - Mezők:
        - `id`
        - `name` (pl. "Partner A", "Partner B")
        - `base_url`
        - `is_active` (bool)

3. `competitor_product_links`
    - Kapcsolótábla a saját termékek és a konkurens termék-URL-ek között
    - Mezők:
        - `id`
        - `product_id` (fk → products)
        - `competitor_id` (fk → competitors)
        - `url` (teljes termék URL)
        - `external_sku` (opcionális – EAN, gyártói kód, ha kiolvasható)
        - `is_broken` (bool) – ha 404/parsing error ismétlődően
        - `last_checked_at` (datetime, opcionális)
        - timestamps

4. `competitor_price_histories`
    - Ár-előzmények táblája
    - Mezők:
        - `id`
        - `competitor_product_link_id` (fk → competitor_product_links)
        - `price` (decimal)
        - `currency` (string, pl. HUF)
        - `fetched_at` (datetime)
        - opcionális: `raw_html_snapshot` vagy `json_payload` (ha akarunk debugot)

### 2.2. Kapcsolatok (Eloquent viselkedés)

- `Product`:
    - `hasMany(CompetitorProductLink)`
- `Competitor`:
    - `hasMany(CompetitorProductLink)`
- `CompetitorProductLink`:
    - `belongsTo(Product)`
    - `belongsTo(Competitor)`
    - `hasMany(CompetitorPriceHistory)`
- `CompetitorPriceHistory`:
    - `belongsTo(CompetitorProductLink)`

---

## Phase 3 – Migrációk (DDL) megírása

Cél: konkrét Laravel migrációk az adatmodell alapján.

### 3.1. Teendő Claude felé

1. Kérd meg, hogy készítse el a következő migrációkat:
    - `create_products_table`
    - `create_competitors_table`
    - `create_competitor_product_links_table`
    - `create_competitor_price_histories_table`

2. Elvárások:
    - Használjon `foreignId` + `constrained()` a fk-kra
    - Megfelelő indexek:
        - `product_id` + `competitor_id` együttes egyedi index a competitor_product_links-ben
        - index `competitor_product_link_id` a price_histories-ben
    - Decimal típus árnak (pl. `decimal('price', 12, 2)`)

---

## Phase 4 – Modellek és kapcsolatok

Cél: Eloquent modellek és kapcsolataik definiálása.

### 4.1. Teendő Claude felé

1. Hozzon létre 4 modellt:
    - `Product`
    - `Competitor`
    - `CompetitorProductLink`
    - `CompetitorPriceHistory`

2. Minden modellben:
    - `protected $fillable` a releváns mezőkre
    - Kapcsolatok:
        - Product:
            - `competitorProductLinks()`
        - Competitor:
            - `productLinks()` vagy `competitorProductLinks()`
        - CompetitorProductLink:
            - `product()`
            - `competitor()`
            - `priceHistories()`
        - CompetitorPriceHistory:
            - `competitorProductLink()`

3. Opcionális helper metódusok (későbbi fázisban is ráér):
    - `CompetitorProductLink::latestPrice()`
    - `Product::priceForCompetitor(Competitor $competitor)`

---

## Phase 5 – Admin felület (mapping kezelés)

Cél: egyszerű UI, ahol termékekhez hozzá tudjuk rendelni az A/B partner URL-jeit.

### 5.1. Scope

- Product edit / detail képernyő:
    - Két input mező:
        - "Partner A URL"
        - "Partner B URL"
- Mentéskor:
    - upsert a `competitor_product_links` táblába
    - `product_id` + `competitor_id` alapján egyediség

### 5.2. Teendő Claude felé

- Ha Filamentet használsz:
    - Kérd meg, hogy a `ProductResource` form részébe tegyen két custom mezőt
    - Mentés logikájához írjon egy afterSave hook-ot, ami kezeli a competitor linkeket
- Ha natív Laravel Blade:
    - Kérj egy controller metódus + form példát, ami a két URL mezőt kezeli és upserteli a `competitor_product_links` rekordokat

---

## Phase 6 – Árlekérdezés logika (fetch & parse)

Cél: konkurens oldalakról ár szedése az ismert termék-URL-ek alapján.

### 6.1. Parser stratégia

- Külön parser partnerenként:
    - `ACompetitorPriceParser`
    - `BCompetitorPriceParser`
- Mindkettő közös interfészt implementál:
    - pl. `CompetitorPriceParserInterface` → metódus: `parsePrice(string $html): ?float`

### 6.2. Árlekérdezés folyamata

1. Lekérni az összes aktív `competitor_product_links` rekordot (opcionálisan batch-ben)
2. Minden linkre:
    - HTTP GET az URL-re
    - `competitor_id` alapján kiválasztani a megfelelő price parser osztályt
    - kinyerni az árat a HTML-ből
    - menteni egy új `competitor_price_histories` rekordot
    - frissíteni `last_checked_at` mezőt a `competitor_product_links`-ben
    - hibák esetén:
        - logolni
        - opcionálisan `is_broken = true`

### 6.3. Teendő Claude felé

- Kérj:
    - Egy `CompetitorPriceParserInterface` interfészt
    - Két konkrét parser class-t stub-okkal (HTML selector helyét kommentben jelölje)
    - Egy service class-t (pl. `CompetitorPriceFetcherService`), amely:
        - átvesz egy `CompetitorProductLink`-et
        - HTTP requestet csinál
        - parserrel kinyeri az árat
        - elmenti a history-t

---

## Phase 7 – Queue, Scheduler, Command

Cél: időzített, skálázható futtatás.

### 7.1. Folyamat

1. Artisan command: `prices:check-competitors`
    - Lekéri az összes aktív `competitor_product_links` rekordot
    - Minden rekordra queue-ba küld egy jobot

2. Job: `CheckCompetitorPrice`
    - Paraméter: `competitor_product_link_id`
    - Meghívja a `CompetitorPriceFetcherService`-t
    - Kezeli a hibákat (retry / mark broken)

3. Scheduler:
    - `app/Console/Kernel.php`-ben:
        - pl. `->hourly()` vagy `->twiceDaily()`

### 7.2. Teendő Claude felé

- Kérj:
    - Artisan command skeleton (prices:check-competitors)
    - Job skeleton (CheckCompetitorPrice)
    - Példa bejegyzés a schedulerbe

---

## Phase 8 – Price Change Alert logika

Cél: ha egy konkurens ár jelentősen változik, jelzés menjen.

### 8.1. Alapelv

- Új ár mentésekor:
    - Lekérni az előző legfrissebb árat ugyanahhoz a `competitor_product_link_id`-hez
    - Kiszámolni az eltérés százalékát
    - Ha nagyobb egy küszöbnél (pl. ±5% vagy ±10%), esemény/notification

### 8.2. Teendő Claude felé

- Kérj:
    - Egy helper metódust a `CompetitorPriceHistory` vagy service rétegben:
        - `handlePriceChange(CompetitorProductLink $link, float $newPrice)`
    - Egy `PriceChanged` event (paraméter: link, oldPrice, newPrice)
    - Egy egyszerű notification (mail vagy log channel), ami jelzi a változást

---

## Phase 9 – Dashboard / riport

Cél: vizuális betekintés, de csak az adatmodell és logika után.

### 9.1. Funkciók

- Termék nézet:
    - saját ár
    - A partner utolsó ár + trend
    - B partner utolsó ár + trend
- Lista nézet:
    - Leginkább alá-/föléárazott termékek
    - Árkülönbség százalékban
- Idősoros grafikon (opcionális):
    - 1–2 kulcstermék ár-trendje 2 konkurensnél

### 9.2. Teendő Claude felé

- Ha lesz rá szükség, később kérd tőle Filament/Nova resource-ok és egyszerű chart példák generálását, miután az előző fázisok készen vannak.

---

## Phase 10 – Hardening, logging, monitoring

Cél: megbízható működés hosszú távon.

### 10.1. Feladatok

- Rate limiting / delay a konkurens oldalak védelmére
- Részletes logging:
    - sikeres / sikertelen lekérések száma
    - 404 / 500 állapotkódok
- Admin jelzés:
    - broken URL lista (ahol tartósan 404/parsing error van)
- Konfigurálhatóság:
    - futtatási gyakoriság
    - price change alert küszöbértéke
    - concurrency / batch méret
