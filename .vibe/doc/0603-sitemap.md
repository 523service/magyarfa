# Sitemap generálás

## Összefoglaló

A rendszer naponta egyszer (hajnali 3:00) automatikusan legenerálja a `public/sitemap.xml` fájlt. A sitemap tartalmazza a főoldalt, az összes látható kategóriát és az összes látható terméket. A keresőmotorok (Google, Bing) ezt a fájlt használják az oldalak felfedezéséhez és indexeléséhez.

---

## Mit tartalmaz a sitemap?

| URL minta | Prioritás | Frissítési gyakoriság |
|---|---|---|
| `/` (főoldal) | 1.0 | daily |
| `/kategoria/{slug}` | 0.8 | weekly |
| `/termek/{slug}` | 0.9 | weekly |

- Csak az `is_visible = true` kategóriák és termékek kerülnek be.
- Kategóriák és termékek `updated_at` mezője kerül `<lastmod>`-ba.
- A keresési oldal (`/kereses`) szándékosan ki van hagyva.

---

## Manuális generálás — parancssor

```bash
php artisan app:generate-sitemap
```

A fájl helye: `public/sitemap.xml`

---

## Automatikus cron

A `app:generate-sitemap` command naponta **hajnali 3:00**-kor fut automatikusan.  
Konfiguráció: `app/Console/Kernel.php` → `schedule()` metódus.

```php
$schedule->command('app:generate-sitemap')->dailyAt('03:00');
```

Localon tesztelni:

```bash
php artisan schedule:run
```

---

## Cron beállítása szerveren

Ha a Laravel Scheduler még nincs beállítva a szerveren, add hozzá ezt a cron bejegyzést:

```bash
crontab -e
```

```
* * * * * cd /var/www/szigeteles && php artisan schedule:run >> /dev/null 2>&1
```

Ez percenként hívja a Laravel Schedulert, ami aztán maga dönti el, mikor kell futtatni az egyes commandokat.

---

## Google Search Console — sitemap beküldése

1. Nyisd meg: [Google Search Console](https://search.google.com/search-console)
2. Válaszd ki a megfelelő property-t
3. Bal oldali menü → **Sitemaps**
4. Add meg az URL-t: `https://yourdomain.hu/sitemap.xml`
5. Kattints **Submit** → a Google elkezdi feldolgozni

---

## Technikai részletek

A generálás a `spatie/laravel-sitemap` csomag builder API-ját használja (nem crawler).  
Ez azt jelenti, hogy az URL-eket közvetlenül az adatbázisból olvassa — nem crawfolja az oldalt, ezért gyors és nem terheli a szervert.

```
spatie/laravel-sitemap
├── SitemapGenerator  — crawler alapú (NEM ezt használjuk)
└── Sitemap::create() — builder alapú (ezt használjuk ✓)
```

---

## Fájlok

```
app/
├── Console/Commands/GenerateSitemap.php   — artisan command
└── Console/Kernel.php                     — cron schedule (dailyAt 03:00)

config/
└── sitemap.php                            — csomag konfiguráció (publish-olt)

public/
└── sitemap.xml                            — generált fájl (git-ignored)

tests/
└── Feature/GenerateSitemapTest.php        — 6 teszt
```

---

## Tesztek

```bash
php artisan test tests/Feature/GenerateSitemapTest.php
```

| Teszt | Mit ellenőriz |
|---|---|
| `test_command_creates_sitemap_file` | A fájl létrejön |
| `test_sitemap_contains_home_url` | Főoldal URL benne van |
| `test_sitemap_contains_visible_category_urls` | Csak látható kategóriák kerülnek be |
| `test_sitemap_contains_visible_product_urls` | Csak látható termékek kerülnek be |
| `test_sitemap_excludes_search_page` | `/kereses` nincs benne |
| `test_sitemap_is_valid_xml` | A generált fájl valid XML |