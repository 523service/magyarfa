# Email visszaigazolás rendelésről PDF melléklettel és link tracking-gel

## Context

A checkout folyamat során rögzített rendelésekről jelenleg **nem megy email visszaigazolás** a vásárlónak. A CheckoutService ugyan fogadja az email címet a checkout adatokban, de:
- Nem tárolja el az Order rekordban
- Nem küld email értesítést
- Nincs PDF számla/visszaigazolás generálás
- Nincs tracking az email megnyitásokról/kattintásokról

Ez a plan **email visszaigazolást** implementál PDF melléklettel és link tracking-gel, hogy az admin lássa, melyik vásárló nyitotta meg az emailt és kattintott a linkekre.

### Felhasználói döntések

Az implementáció az alábbi technológiai választások alapján történik:
- **PDF library**: `barryvdh/laravel-dompdf` (egyszerű setup, nincs Node.js/Puppeteer függőség) - ez a csomag már telepítve!
- **Email küldés**: Szinkron (nincs queue worker, checkout közben elküldi)
- **Link tracking**: Custom megoldás (saját adatbázis táblák, Filament admin UI)

## Architektúra

### Komponensek

```
┌─────────────────────────────────────────────────────────────────┐
│ CheckoutService::placeOrder()                                   │
│  1. Order létrehozása (+ email tárolás)                         │
│  2. OrderItems, Addresses, Payment létrehozása                  │
│  3. OrderPlaced event fire                                      │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ SendOrderConfirmationEmail │ (Listener)
        │  1. EmailSend rekord       │
        │  2. PDF generálás          │
        │  3. Email küldés           │
        └────────────┬───────────────┘
                     │
         ┌───────────┴──────────────┐
         ▼                          ▼
  ┌─────────────┐          ┌──────────────┐
  │ OrderPdf    │          │ OrderConfirm │
  │ Service     │          │ ation        │
  │ (PDF gen)   │          │ (Mailable)   │
  └─────────────┘          └──────┬───────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │ Email Template  │
                         │ + Tracking pixel│
                         │ + Tracked links │
                         └─────────────────┘

Admin felület:
┌────────────────────────────────────────┐
│ Filament EmailSendResource             │
│  - Táblázat: rendelés, email, státusz  │
│  - Szűrők: megnyitva/nem, dátum        │
│  - Detail: kattintások (IP, UA, URL)   │
└────────────────────────────────────────┘
```

### Event-driven flow

**Event**: `OrderPlaced` (Order létrehozás után)
**Listener**: `SendOrderConfirmationEmail` (email küldés + tracking)

Ez biztosítja, hogy a CheckoutService felelőssége **csak a rendelés létrehozása**, az email küldés teljesen elkülönül (loose coupling).

### Database Schema

#### Migration 1: Email mező hozzáadása `shop_orders` táblához

```php
Schema::table('shop_orders', function (Blueprint $table) {
    $table->string('email')->after('shop_customer_id')->nullable();
});
```

**Indoklás**: Vendég rendeléseknél nincs Customer rekord, de az email-t tárolni kell.

#### Migration 2: Email tracking tábla

```php
Schema::create('email_sends', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_order_id')->constrained()->cascadeOnDelete();
    $table->string('recipient_email');
    $table->string('subject');
    $table->string('tracking_token', 32)->unique();
    $table->timestamp('sent_at');
    $table->timestamp('opened_at')->nullable();
    $table->integer('open_count')->default(0);
    $table->integer('click_count')->default(0);
    $table->timestamps();

    $table->index('tracking_token');
});
```

**Mezők**:
- `tracking_token`: 32 karakteres random string (gyors lookup index-szel)
- `opened_at`: első megnyitás időpontja (tracking pixel alapján)
- `open_count`: hányszor nyitották meg (ugyanaz az email többször is megnyitható)
- `click_count`: összes link kattintás száma

#### Migration 3: Link kattintások tracking

```php
Schema::create('email_link_clicks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('email_send_id')->constrained()->cascadeOnDelete();
    $table->string('url');
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamp('clicked_at');
    $table->timestamps();
});
```

**Mezők**:
- `url`: eredeti target URL (ahova redirect-el)
- `ip_address`: IPv4/IPv6 cím (GDPR: később csonkítható)
- `user_agent`: böngésző info (debug célra)

## Implementációs lépések

### 1. Foundation: Database & Models

**1.1 Migrations létrehozása**

```bash
php artisan make:migration add_email_to_shop_orders --table=shop_orders
php artisan make:migration create_email_sends_table
php artisan make:migration create_email_link_clicks_table
```

**1.2 Models létrehozása**

```bash
php artisan make:model EmailSend
php artisan make:model EmailLinkClick
```

**1.3 Order model frissítése**

- `email` hozzáadása a `$fillable` tömbhöz
- `emailSends()` HasMany relationship hozzáadása

**1.4 Relationships beállítása**

- `EmailSend`: `order()` BelongsTo, `clicks()` HasMany
- `EmailLinkClick`: `emailSend()` BelongsTo

**Testing**: Factory-k létrehozása, relationships tesztelése

---

### 2. PDF Generation

**2.1 Package telepítés**

```bash
composer require barryvdh/laravel-dompdf
```

**2.2 PDF Blade template létrehozása**

Fájl: `resources/views/pdfs/order-invoice.blade.php`

Tartalom:
- A4 méret, print-friendly stílusok
- Magyar címek: "RENDELÉS VISSZAIGAZOLÁS"
- Order adatok: rendelésszám, dátum, státusz
- Termékek táblázat (termék név, mennyiség, egységár, összeg)
- Összesítő: részösszeg, szállítás, végösszeg
- Szállítási és számlázási cím
- Magyar szám formázás: `15 000 Ft` (space separator)

**Alapja**: `resources/views/livewire/shop/checkout/order-confirmation.blade.php` (adaptálva)

**2.3 OrderPdfService létrehozása**

Fájl: `app/Services/OrderPdfService.php`

```php
public function generate(Order $order): string
{
    $order->load(['items.product', 'addresses', 'payments']);

    $pdf = Pdf::loadView('pdfs.order-invoice', [
        'order' => $order,
    ]);

    $filename = "rendeles-{$order->number}.pdf";
    $path = storage_path("app/pdf/{$filename}");

    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $pdf->save($path);

    return $path;
}
```

**Testing**: `OrderPdfServiceTest` - PDF létrejön, magyar karakterek OK, file size > 0

---

### 3. Email Tracking Service

**3.1 Config fájl**

Fájl: `config/email-tracking.php`

```php
return [
    'enabled' => env('EMAIL_TRACKING_ENABLED', true),
    'pixel_route' => 'email.pixel',
    'click_route' => 'email.click',
];
```

**3.2 EmailTrackingService létrehozása**

Fájl: `app/Services/EmailTrackingService.php`

**Metódusok**:
- `createTracking(Order, email)`: EmailSend rekord + tracking token generálás
- `getTrackingPixelUrl(token)`: pixel route URL
- `getTrackedLinkUrl(token, targetUrl)`: tracked redirect URL
- `recordOpen(token)`: tracking pixel hit → opened_at + open_count++
- `recordClick(token, url, request)`: EmailLinkClick rekord + click_count++

**Testing**: `EmailTrackingServiceTest` - minden metódus unit tesztelése

**3.3 EmailTrackingController**

Fájl: `app/Http/Controllers/EmailTrackingController.php`

**Routes** (`routes/web.php`):

```php
Route::get('/email/pixel/{token}.png', [EmailTrackingController::class, 'pixel'])
    ->name('email.pixel');
Route::get('/email/click/{token}', [EmailTrackingController::class, 'click'])
    ->name('email.click');
```

**pixel() metódus**:
- `EmailTrackingService::recordOpen()` hívás
- 1x1 transzparens PNG visszaadása
- `Cache-Control: no-store` header (ne cache-elje a kliens)

**click() metódus**:
- URL dekódolás (base64)
- `EmailTrackingService::recordClick()` hívás
- Redirect az eredeti URL-re

**Testing**: `EmailTrackingTest` - pixel/click route-ok tesztelése, DB update ellenőrzés

---

### 4. Email Implementation

**4.1 Mailable létrehozása**

```bash
php artisan make:mail OrderConfirmation
```

Fájl: `app/Mail/OrderConfirmation.php`

**Constructor**:
```php
public function __construct(
    public Order $order,
    public string $trackingToken
) {
    $this->order->load(['items.product', 'addresses', 'payments']);
}
```

**build() metódus**:
```php
public function build(OrderPdfService $pdfService): self
{
    $pdfPath = $pdfService->generate($this->order);

    return $this->subject('Rendelés visszaigazolás: ' . $this->order->number)
        ->view('emails.order-confirmation')
        ->text('emails.order-confirmation-text')
        ->attach($pdfPath, [
            'as' => "rendeles-{$this->order->number}.pdf",
            'mime' => 'application/pdf',
        ])
        ->with([
            'order' => $this->order,
            'trackingPixelUrl' => route('email.pixel', ['token' => $this->trackingToken]),
            'orderUrl' => route('order.confirmation', ['number' => $this->order->number]),
        ]);
}
```

**4.2 Email templates**

**HTML** (`resources/views/emails/order-confirmation.blade.php`):
- Magyar tartalom: "Köszönjük a rendelését!"
- Order összefoglaló (rendelésszám, dátum, összeg)
- Termékek listája
- Gomb: "Rendelés megtekintése" → tracked link
- Tracking pixel a végén: `<img src="{{ $trackingPixelUrl }}" width="1" height="1" alt="" />`

**Plain text** (`resources/views/emails/order-confirmation-text.blade.php`):
- Ugyanaz HTML nélkül
- Linkek direct URL-ek (nem tracked - text emailben amúgy sem működik pixel)

**Testing**: `Mail::fake()` + assertion PDF attached, subject correct

---

### 5. Event-Driven Dispatch

**5.1 Event osztály**

```bash
php artisan make:event OrderPlaced
```

Fájl: `app/Events/OrderPlaced.php`

```php
class OrderPlaced
{
    use Dispatchable, SerializableProperties;

    public function __construct(public Order $order) {}
}
```

**5.2 Listener osztály**

```bash
php artisan make:listener SendOrderConfirmationEmail --event=OrderPlaced
```

Fájl: `app/Listeners/SendOrderConfirmationEmail.php`

```php
public function __construct(
    protected EmailTrackingService $trackingService,
) {}

public function handle(OrderPlaced $event): void
{
    $order = $event->order;

    if (!$order->email) {
        Log::warning("Order {$order->number} has no email, skipping.");
        return;
    }

    // Tracking record
    $emailSend = $this->trackingService->createTracking(
        $order,
        $order->email
    );

    // Send email (sync)
    Mail::to($order->email)
        ->send(new OrderConfirmation($order, $emailSend->tracking_token));
}
```

**5.3 EventServiceProvider regisztráció**

Fájl: `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    OrderPlaced::class => [
        SendOrderConfirmationEmail::class,
    ],
    // ... existing
];
```

**5.4 CheckoutService módosítás**

**Módosítás 1**: Order::create()-ben email tárolás

```php
$order = Order::create([
    'number' => $this->generateOrderNumber(),
    'email' => $data['email'],  // ÚJ SOR
    'status' => OrderStatus::New,
    // ... többi mező
]);
```

**Módosítás 2**: Event fire a transaction vége előtt, de CART CLEAR UTÁN

```php
$this->cartService->clear();

event(new OrderPlaced($order));  // ÚJ SOR

return $order;
```

**Testing**: `CheckoutServiceTest` frissítése - Event::fake(), assertDispatched

---

### 6. Filament Admin Interface

**6.1 Resource létrehozása**

```bash
php artisan make:filament-resource EmailSend --generate
```

**6.2 EmailSendResource konfiguráció**

Fájl: `app/Filament/Resources/Shop/EmailSendResource.php`

**Table** (`app/Filament/Resources/Shop/EmailSends/Tables/EmailSendsTable.php`):

Oszlopok:
- Order Number (link OrderResource edit page-hez)
- Recipient Email (searchable)
- Subject
- Sent At (sortable, default desc)
- Opened At (badge: green ha van, gray ha null)
- Open Count (badge)
- Click Count (badge)

Szűrők:
- Megnyitva/Nem nyitva (opened_at null check)
- Dátum range (sent_at)

**6.3 Detail page / Relation Manager**

**Opció A**: EmailLinkClick relation manager
- Táblázat: URL, Clicked At, IP, User Agent

**Opció B**: Infolist widget
- Timeline: Sent → Opened → Clicks

**6.4 Navigation**

Csoport: "Shop"
Icon: `heroicon-o-envelope`
Label: "Email követés"

**Testing**: Filament tests (optional) - resource elérhető, táblázat renderel

---

### 7. Testing Suite

**Unit Tests**:

1. `OrderPdfServiceTest`
   - `test_generates_pdf_for_order()`
   - `test_pdf_contains_hungarian_characters()`
   - `test_pdf_file_exists_after_generation()`

2. `EmailTrackingServiceTest`
   - `test_creates_tracking_record()`
   - `test_generates_unique_tracking_token()`
   - `test_records_email_open()`
   - `test_records_link_click()`
   - `test_increments_counters_on_multiple_opens()`

**Feature Tests**:

3. `OrderConfirmationEmailTest`
   - `test_email_sent_after_order_placed()`
   - `test_email_contains_pdf_attachment()`
   - `test_email_subject_is_correct_hungarian()`
   - `test_no_email_sent_if_order_has_no_email()`
   - `test_tracking_record_created()`

4. `EmailTrackingTest`
   - `test_pixel_route_records_open()`
   - `test_pixel_returns_transparent_png()`
   - `test_click_route_records_click_and_redirects()`
   - `test_click_stores_ip_and_user_agent()`
   - `test_invalid_token_returns_404()`

5. `CheckoutServiceTest` (frissítés)
   - `test_order_stores_email()` (ÚJ)
   - `test_order_placed_event_fired()` (ÚJ)

**Integration Test**:

6. `CheckoutToEmailFlowTest`
   - End-to-end: checkout → order → event → email → PDF → tracking
   - Teljes flow szimulálása

**Magyar nyelv tesztelés**:
- Email subject: "Rendelés visszaigazolás: OR12345678"
- PDF tartalom: magyar spec. karakterek (ő, ű, á, stb.)
- Szám formázás: `15 000 Ft` (space separator)

---

### 8. Configuration & Documentation

**8.1 .env.example frissítése**

```env
# Mail settings
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=info@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Email Tracking
EMAIL_TRACKING_ENABLED=true
```

**8.2 README frissítés** (opcionális, de hasznos)

Új szekció: Email System

```markdown
## Email visszaigazolás

Rendelés leadása után automatikus email megy PDF melléklettel.

### Development
- MailHog: http://localhost:8025 (email preview)
- PDF tárolás: storage/app/pdf/

### Admin
- Email tracking: Admin Panel → Shop → Email követés
```

---

## Kritikus fájlok listája

### Létrehozandó fájlok (új)

1. **Migrations**:
   - `database/migrations/xxxx_add_email_to_shop_orders.php`
   - `database/migrations/xxxx_create_email_sends_table.php`
   - `database/migrations/xxxx_create_email_link_clicks_table.php`

2. **Models**:
   - `app/Models/EmailSend.php`
   - `app/Models/EmailLinkClick.php`

3. **Services**:
   - `app/Services/OrderPdfService.php`
   - `app/Services/EmailTrackingService.php`

4. **Events & Listeners**:
   - `app/Events/OrderPlaced.php`
   - `app/Listeners/SendOrderConfirmationEmail.php`

5. **Mail**:
   - `app/Mail/OrderConfirmation.php`

6. **Controllers**:
   - `app/Http/Controllers/EmailTrackingController.php`

7. **Views**:
   - `resources/views/pdfs/order-invoice.blade.php`
   - `resources/views/emails/order-confirmation.blade.php`
   - `resources/views/emails/order-confirmation-text.blade.php`

8. **Filament**:
   - `app/Filament/Resources/Shop/EmailSendResource.php`
   - `app/Filament/Resources/Shop/EmailSends/Pages/` (List, Create, Edit)
   - `app/Filament/Resources/Shop/EmailSends/Schemas/EmailSendForm.php`
   - `app/Filament/Resources/Shop/EmailSends/Tables/EmailSendsTable.php`

9. **Tests**:
   - `tests/Unit/OrderPdfServiceTest.php`
   - `tests/Unit/EmailTrackingServiceTest.php`
   - `tests/Feature/OrderConfirmationEmailTest.php`
   - `tests/Feature/EmailTrackingTest.php`
   - `tests/Feature/CheckoutToEmailFlowTest.php`

10. **Config**:
    - `config/email-tracking.php`

### Módosítandó fájlok (existing)

1. `app/Models/Shop/Order.php`
   - `email` hozzáadása `$fillable`-hez
   - `emailSends()` relationship

2. `app/Services/CheckoutService.php`
   - Order::create()-ben email tárolás
   - Event fire (`OrderPlaced`)

3. `app/Providers/EventServiceProvider.php`
   - Listener regisztráció

4. `routes/web.php`
   - Tracking routes (pixel, click)

5. `tests/Feature/CheckoutServiceTest.php`
   - Új tesztek: email tárolás, event fire

6. `.env.example`
   - Email tracking config

---

## Verification (Tesztelés a végén)

### 1. Migration & Database

```bash
php artisan migrate
```

Ellenőrzés:
- `shop_orders` táblában van `email` oszlop
- `email_sends` és `email_link_clicks` táblák létrejöttek

### 2. PDF Generation

```bash
php artisan tinker
```

```php
$order = Order::factory()->create(['email' => 'test@example.com']);
$service = app(OrderPdfService::class);
$path = $service->generate($order);
echo $path; // storage/app/pdf/rendeles-XXX.pdf
```

Ellenőrzés:
- PDF fájl létezik
- Megnyitható PDF viewer-ben
- Magyar karakterek rendben renderelnek

### 3. Email Sending (dev)

```bash
php artisan tinker
```

```php
$order = Order::factory()->create(['email' => 'test@example.com']);
event(new OrderPlaced($order));
```

Ellenőrzés:
- MailHog (localhost:8025) - email megjelenik
- PDF melléklet van
- Tracking pixel src URL valid
- "Rendelés megtekintése" gomb tracked link

### 4. Tracking

**Email megnyitás**:
- Nyisd meg az emailt MailHog-ban
- Pixel betöltődik (nincs broken image)
- Adminban (EmailSendResource) látszik `opened_at` + `open_count = 1`

**Link kattintás**:
- Kattints a "Rendelés megtekintése" gombra
- Redirect működik
- Adminban `click_count = 1`
- `email_link_clicks` táblában van rekord (IP, UA)

### 5. End-to-End Flow

Teljes checkout:
1. Kosárba termék
2. Checkout → email megadása
3. Rendelés leadása
4. MailHog-ban email megjelenik
5. PDF letölthető
6. Admin panel - EmailSendResource - megjelenik a küldés

### 6. Tests

```bash
php artisan test --filter=OrderPdf
php artisan test --filter=EmailTracking
php artisan test --filter=OrderConfirmation
php artisan test --filter=Checkout
```

Minden teszt zöld kell legyen.

### 7. Code Quality

```bash
vendor/bin/pint --dirty
```

Formázás ellenőrzés.

---

## Trade-offs és döntések

### 1. Szinkron vs Queue

**Választott**: Szinkron (Mail::send, nem queue)

**Előnyök**:
- ✅ Egyszerűbb setup (nincs queue worker)
- ✅ Email azonnal megy (nincs késleltetés)
- ✅ Hibaüzenetek azonnal láthatók

**Hátrányok**:
- ❌ Checkout 2-3 mp lassabb (SMTP wait)
- ❌ Nincs retry failed send esetén

**Miért jó így**: Demo alkalmazás, egyszerűség prioritás.

### 2. barryvdh/dompdf vs Spatie PDF

**Választott**: barryvdh/laravel-dompdf

**Előnyök**:
- ✅ Nincs Node.js/Puppeteer függőség
- ✅ Egyszerű composer install
- ✅ Elég jó magyar karakter támogatás

**Hátrányok**:
- ❌ Gyengébb CSS támogatás (flexbox, grid limitált)
- ❌ Néha layout quirk-ök

**Miért jó így**: Egyszerűség, gyors setup, megfelelő minőség számlához.

### 3. Custom Tracking vs Postmark

**Választott**: Custom tracking

**Előnyök**:
- ✅ Teljes kontroll
- ✅ GDPR-barát (adatok nálunk)
- ✅ Nincs havi költség
- ✅ Filament admin integráció

**Hátrányok**:
- ❌ Több kód írás
- ❌ Mi felelünk a tracking pontosságért

**Miért jó így**: Demo app, tanulási célok, cost control.

---

## Magyar nyelv specifikus részletek

### Email szövegek

**Subject**: `Rendelés visszaigazolás: {order_number}`
**Köszönés**: `Köszönjük a rendelését!`
**CTA gomb**: `Rendelés megtekintése`
**Footer**: `Kérdése van? Írjon nekünk: info@example.com`

### PDF tartalom

**Cím**: `RENDELÉS VISSZAIGAZOLÁS`
**Mezők**:
- Rendelésszám
- Dátum
- Fizetési mód
- Szállítási mód
- Rendelt termékek
- Részösszeg
- Szállítás
- Végösszeg

**Számformázás**: `number_format($val, 0, ',', ' ') . ' Ft'` → `15 000 Ft`

### Filament labels

**Navigation**: "Email követés"
**Oszlopok**: "Rendelés", "Címzett", "Tárgy", "Elküldve", "Megnyitva", "Megnyitások", "Kattintások"
**Szűrők**: "Megnyitva", "Nem nyitva", "Dátum"

---

## Future enhancements (scope-on kívül)

- Multiple email templates (order shipped, delivered, cancelled)
- Admin "resend email" action
- Email preview before send
- A/B testing (subject lines)
- SMS notification integration
- Multi-language support (EN/HU)
- Email unsubscribe link (marketing emails esetén)
- Advanced analytics (open rate %, best send time)

---

## Összefoglalás

Ez a plan egy **teljes email visszaigazoló rendszert** valósít meg:
- ✅ Automatikus email küldés rendelés után
- ✅ PDF számla melléklet magyar nyelven
- ✅ Email megnyitás tracking (pixel)
- ✅ Link kattintás tracking (redirect)
- ✅ Filament admin UI a tracking adatok megtekintésére
- ✅ Event-driven architektúra (loose coupling)
- ✅ Egyszerű setup (dompdf, szinkron, custom tracking)

**Implementációs sorrend**: Database → PDF → Tracking → Email → Event → Filament → Tests

**Becsült idő**: 3-4 óra (tapasztalt Laravel dev esetén)

**Futtatás után**: vendor/bin/pint --dirty + php artisan test
