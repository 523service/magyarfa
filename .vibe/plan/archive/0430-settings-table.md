# Store Settings — Filament Admin Page

## Context

A `../../../config/shop.php` jelenleg tartalmazza a nyitvatartást, szállítási díjakat és figyelmeztetéseket. Ezeket kiszervezzük egy Filament admin Settings Page-re, ahol az adminisztrátor DB-ből módosíthatja őket kódírás nélkül. A `spatie/laravel-settings` (v3.5.0) és a `filament/spatie-laravel-settings-plugin` (v4.2.0) **már telepítve van**, a `settings` tábla **már létezik**. Csak a beállítási osztályokat és az admin oldalt kell létrehozni.

---

## Fájlok, amelyek érintve lesznek

| Fájl | Változás |
|---|---|
| `app/Settings/ShopSettings.php` | **ÚJ** — Spatie typed settings osztály |
| `database/settings/2026_xx_xx_create_shop_settings.php` | **ÚJ** — kezdőértékek migrációja |
| `app/Filament/Pages/ManageShopSettings.php` | **ÚJ** — Filament SettingsPage |
| `../../../app/Providers/Filament/AdminPanelProvider.php` | plugin + nav group regisztrálás |
| `../../../app/Services/ShippingService.php` | `config()` → `ShopSettings` injektálás |
| `../../../resources/views/partials/shop/header.blade.php` | `config('shop.opening_hours')` → `ShopSettings` |
| `../../../tests/Feature/ShippingServiceTest.php` | `Config::set()` → `ShopSettings::fake()` |
| `../../../config/shop.php` | takarítás (áthelyezett értékek kivétele) |

---

## Lépések

### 1. Spatie Settings config publikálása (ha szükséges)
```bash
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings"
```
Létrehozza a `config/settings.php`-t az appban (auto-discover, path: `app/Settings/`).

---

### 2. `ShopSettings` osztály — `app/Settings/ShopSettings.php`

```php
use Spatie\LaravelSettings\Settings;

class ShopSettings extends Settings
{
    // Általános
    public string $store_email;
    public string $store_phone;
    public string $store_address;

    // Szállítás
    public int $free_shipping_threshold;
    public int $courier_price;

    // Nyitvatartás
    public string $weekday_open;
    public string $weekday_close;
    public ?string $saturday_open;
    public ?string $saturday_close;
    // Vasárnap: mindig zárva (nincs mező, UI hardcode)

    // Figyelmeztetések
    public bool $price_volatility_enabled;
    public string $price_volatility_text;

    // Speciális napok — indexed array:
    // [['date' => '2026-04-03', 'label' => 'Nagypéntek', 'hours' => null], ...]
    public array $special_days;

    public static function group(): string { return 'shop'; }
}
```

---

### 3. Settings migráció — `database/settings/2026_xx_xx_create_shop_settings.php`

```bash
php artisan make:settings-migration CreateShopSettings
```

Feltölti a jelenlegi `../../../config/shop.php` értékeit alapértékként:
- `store_email`: pl. `'info@szigeteles.hu'`
- `store_phone`: pl. `'+36 1 234 5678'`
- `store_address`: pl. `'1234 Budapest, Példa u. 1.'`
- `free_shipping_threshold`: `550000`
- `courier_price`: `59900`
- `weekday_open/close`: `'06:00'` / `'17:00'`
- `saturday_open/close`: `'06:00'` / `'12:00'`
- `price_volatility_enabled`: `true`
- `price_volatility_text`: jelenlegi szöveg
- `special_days`: jelenlegi `special_days` tömb konvertálva indexed formátumra

---

### 4. Filament Page — `app/Filament/Pages/ManageShopSettings.php`

Extends: `Filament\SpatieLaravelSettingsPlugin\Pages\SettingsPage`

```php
protected static string $settings = ShopSettings::class;
protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
protected static ?string $navigationGroup = 'Beállítások';
protected static ?string $navigationLabel = 'Bolt beállítások';
```

**Form struktúra (Tabs):**

- **Általános** tab: `store_email` (Email), `store_phone` (Text), `store_address` (Textarea)
- **Szállítás** tab: `free_shipping_threshold` (Numeric, Ft), `courier_price` (Numeric, Ft)
- **Nyitvatartás** tab:
  - Section "Heti rendes nyitvatartás": `weekday_open`, `weekday_close` (Time inputs), `saturday_open`, `saturday_close` (nullable time inputs)
  - Section "Speciális napok": Repeater `special_days` → `date` (Date picker), `label` (Text), `hours` (Text, nullable — pl. `'06:00–12:00'`, üres = zárva)
- **Figyelmeztetések** tab: `price_volatility_enabled` (Toggle), `price_volatility_text` (Textarea)

---

### 5. AdminPanelProvider módosítás

**Plugin regisztrálás:**
```php
->plugins([
    \Filament\SpatieLaravelSettingsPlugin\SpatieLaravelSettingsPlugin::make(),
])
```

**Navigációs csoport hozzáadása:**
```php
->navigationGroups([
    'Shop',
    'Products',
    'Blog',
    'Beállítások',  // ← ÚJ
])
```

---

### 6. ShippingService frissítése

**Jelenlegi (`config()` alapú):**
```php
public function getCourierBasePrice(): int { return config('shop.courier_price'); }
public function getFreeThreshold(): int { return config('shop.free_shipping_threshold'); }
```

**Új (constructor injection):**
```php
public function __construct(private ShopSettings $settings) {}
public function getCourierBasePrice(): int { return $this->settings->courier_price; }
public function getFreeThreshold(): int { return $this->settings->free_shipping_threshold; }
```

---

### 7. header.blade.php frissítése (133. sor körül)

A `@php` blokk elején:
```php
// Régi:
$hours = config('shop.opening_hours');

// Új:
$settings = app(\App\Settings\ShopSettings::class);
$hours = [
    'weekday'      => ['open' => $settings->weekday_open, 'close' => $settings->weekday_close],
    'saturday'     => $settings->saturday_open
                        ? ['open' => $settings->saturday_open, 'close' => $settings->saturday_close]
                        : null,
    'sunday'       => null,
    'special_days' => collect($settings->special_days)
                        ->keyBy('date')
                        ->map(fn ($d) => ['label' => $d['label'], 'hours' => $d['hours'] ?: null])
                        ->all(),
];
```

A blade többi logikája (`$isOpen`, `$upcomingSpecial`, stb.) **változatlan marad**.

---

### 8. Tesztek frissítése

A `ShippingServiceTest.php` jelenleg `Config::set('shop.courier_price', ...)` hívásokkal dolgozik.

**Új megközelítés** (Spatie `Settings::fake()` metódus):
```php
use App\Settings\ShopSettings;

ShopSettings::fake([
    'courier_price' => 59900,
    'free_shipping_threshold' => 550000,
]);
$service = app(ShippingService::class);
```

Minden `Config::set('shop.*')` hívás cserélendő.

---

### 9. config/shop.php takarítás

Az áthelyezett értékeket eltávolítjuk / kommentbe tesszük:
- `free_shipping_threshold` ✂
- `courier_price` ✂
- `notices` ✂
- `opening_hours` ✂

A fájl maradhat üres stubként vagy törölhető teljesen.

---

### 10. Caching (opcionális, ajánlott)

`../../../.env` fájlba:
```
SETTINGS_CACHE_ENABLED=true
```

Ez megakadályozza, hogy minden page load DB-lekérést indítson a beállításokhoz.

---

## Ellenőrzés / Tesztelés

1. `php artisan migrate` — settings migráció lefut, `settings` táblában megjelennek a `shop` group értékek
2. Filament admin → "Beállítások" → "Bolt beállítások" oldal betölt, tabokkal
3. Értéket módosítunk → mentés → frontenden azonnal látszik a változás
4. `php artisan test tests/Feature/ShippingServiceTest.php` — mind a 7 teszt zöld
5. Shop frontend header betölt, nyitvatartás megjelenik, ünnepnapok is helyesen
6. `vendor/bin/pint --dirty` — formázás

---

## Sorrend

1. `ShopSettings` osztály létrehozása
2. Settings migráció + futtatása
3. Filament Page létrehozása + AdminPanelProvider
4. ShippingService frissítése
5. header.blade.php frissítése
6. Tesztek frissítése
7. config/shop.php takarítás
8. Pint + teljes teszt suite
