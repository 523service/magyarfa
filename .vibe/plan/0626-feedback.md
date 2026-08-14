# Feedback Reporter — Implementációs Terv

## Kontextus

A látogatók könnyen tudják jelezni a hibákat/problémákat az oldalon. A gomb mindig látható, jobb-lenn, minden látogatói oldalon (shop layout + shop-auth layout). A bejelentések Filament adminban kezelhetők, státusszal szűrhetők. Az adminnak email értesítő is megy.

---

## Megválaszolandó döntések

| Kérdés | Válasz |
|--------|--------|
| Melyik layoutokon jelenik meg | shop + shop-auth (minden látogatói oldal) |
| Email értesítő | Igen, `feedback_email` → `config/shop.php` |
| Filament hely | Önálló: `app/Filament/Resources/Feedbacks/` |
| Screenshot | DB-ben `screenshot TEXT nullable` előkészítve; UI-ban disabled placeholder gomb |

---

## Lépések

### 1. FeedbackStatus Enum — `app/Enums/FeedbackStatus.php`
```php
enum FeedbackStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
```
Színek: New=info, InProgress=warning, Resolved=success, Closed=gray  
Ikonok: heroicon-m-sparkles, heroicon-m-arrow-path, heroicon-m-check-badge, heroicon-m-x-circle

---

### 2. Migration — `feedbacks` tábla
```
php artisan make:migration create_feedbacks_table
```
Oszlopok:
- `id`
- `user_id` (nullable, FK → users, nullOnDelete)
- `name` string
- `email` string
- `description` text
- `url` string
- `status` string (default: 'new')
- `device_info` json nullable (IP, user agent, Accept-Language, screen méret)
- `screenshot` text nullable (előkészítve, egyelőre mindig null)
- `meta` json nullable
- `timestamps`

---

### 3. Feedback Model — `app/Models/Feedback.php`
```php
php artisan make:model Feedback --factory
```
- `$fillable`: name, email, description, url, status, device_info, screenshot, user_id, meta
- `casts()`: status → FeedbackStatus::class, device_info → 'array', meta -> 'array'
- `belongsTo(User::class)` (nullable)
- Factory: fake name/email/description/url/status

---

### 4. Config módosítás — `config/shop.php`
Hozzáadás:
```php
'feedback_email' => env('FEEDBACK_EMAIL', env('STORE_EMAIL', '')),
```

---

### 5. Notification — `app/Notifications/NewFeedbackNotification.php`
```
php artisan make:notification NewFeedbackNotification
```
- `toMail()`: Feedback tárgya + leírás + URL link
- Küldés: `Notification::route('mail', config('shop.feedback_email'))->notify(new NewFeedbackNotification($feedback))`
- Queued: igen (`implements ShouldQueue`)

---

### 6. Livewire Komponens — `app/Livewire/FeedbackButton.php`
```
php artisan make:livewire FeedbackButton
```

**PHP osztály tulajdonságai:**
```php
public bool $showModal = false;
public string $name = '';
public string $email = '';
public string $description = '';
public string $currentUrl = '';
public int $screenWidth = 0;
public int $screenHeight = 0;
```

**`mount()`**: Ha be van jelentkezve → name/email prefill Auth::user()-ből

**`openModal()`**: `$this->showModal = true`

**`closeModal()`**: `$this->showModal = false; $this->reset(['description'])`

**`submit()`**:
- Validate: name (required), email (required|email), description (required|min:10)
- Feedback::create([...device_info => ['ip' => request()->ip(), 'user_agent' => request()->userAgent(), 'accept_language' => request()->header('Accept-Language'), 'screen_width' => $this->screenWidth, 'screen_height' => $this->screenHeight]])
- Notification küldés (try/catch)
- Modal bezárás + success üzenet (Livewire dispatch + Alpine x-show)

**Blade view** (`resources/views/livewire/feedback-button.blade.php`):

```
<div>
  <!-- Floating Button: fixed bottom-6 right-6 z-30 -->
  <!-- pill alakú: bg-accent text-white, rounded-full py-2 px-3 -->
  <!-- Icon: heroicon-o-bug-ant + "Hiba" szöveg desktop-on -->
  
  <!-- Alpine init: currentUrl/screenWidth/screenHeight beállítás -->
  <div x-init="$wire.currentUrl = window.location.href;
               $wire.screenWidth = screen.width;
               $wire.screenHeight = screen.height;">
  
  <!-- Backdrop + Modal (cart-modal pattern) -->
  <!-- max-w-sm w-full, mobilon 100% wide -->
  <!-- Mezők: Név, Email, Leírás (textarea rows=4) -->
  <!-- Screenshot gomb: disabled + "Hamarosan" badge -->
  <!-- Küldés gomb -->
</div>
```

**Z-index rétegek:**
- Floating gomb: `z-30`
- Modal backdrop: `z-[60]`
- Modal: `z-[61]`
(Cart modal backdrop z-40, modal z-50 — a feedback modal fölötte van)

---

### 7. Layout integráció

**`resources/views/components/layouts/shop.blade.php`** — a `@livewireScripts` elé:
```blade
<livewire:feedback-button />
```

**`resources/views/components/layouts/shop-auth.blade.php`** — szintén a `@livewireScripts` elé.

---

### 8. Filament Resource

**Struktúra:**
```
app/Filament/Resources/Feedbacks/
├── FeedbackResource.php
├── Pages/
│   ├── ListFeedbacks.php
│   └── ViewFeedback.php   ← View page (readonly + status szerkesztés)
├── Schemas/
│   └── FeedbackForm.php   ← status Select, device_info TextEntry-k
└── Tables/
    └── FeedbacksTable.php
```

**FeedbackResource.php:**
- `$model = Feedback::class`
- `$navigationIcon = 'heroicon-o-bug-ant'`
- `$navigationLabel = 'Visszajelzések'`
- `$navigationGroup = 'Általános'`
- getPages(): index + view

**FeedbacksTable.php oszlopok:**
- `TextColumn::make('name')`
- `TextColumn::make('email')`
- `TextColumn::make('description')->limit(60)`
- `TextColumn::make('status')->badge()`
- `TextColumn::make('url')->limit(50)->url(fn($record) => $record->url)`
- `TextColumn::make('created_at')->dateTime()->sortable()`

**Szűrők:**
```php
SelectFilter::make('status')->options(FeedbackStatus::class)
```

**ViewFeedback oldal:** status szerkeszthető Select + összes adat megjelenítése (TextEntry-k).

---

### 9. Tests

**`tests/Feature/Livewire/FeedbackButtonTest.php`:**
- `feedback_button_renders` — komponens renderel
- `modal_opens_and_closes` — openModal/closeModal
- `prefills_name_email_when_logged_in` — Auth user → prefill
- `validates_required_fields` — üres submit → errors
- `guest_can_submit_feedback` — sikeres submit, DB-ben van
- `logged_in_user_can_submit_feedback` — user_id be van állítva
- `notification_sent_on_submit` — Notification::fake()

**`tests/Feature/Filament/FeedbackResourceTest.php`:**
- `feedback_list_loads`
- `can_filter_by_status`
- `can_view_feedback`

---

## Fájlok összefoglalója

| Új fájl | Megjegyzés |
|---------|------------|
| `app/Enums/FeedbackStatus.php` | Enum HasColor/HasIcon/HasLabel |
| `database/migrations/..._create_feedbacks_table.php` | Migration |
| `app/Models/Feedback.php` | Eloquent model + factory |
| `database/factories/FeedbackFactory.php` | Factory |
| `config/shop.php` | +feedback_email sor |
| `app/Notifications/NewFeedbackNotification.php` | Email értesítő |
| `app/Livewire/FeedbackButton.php` | Livewire komponens |
| `resources/views/livewire/feedback-button.blade.php` | Blade view |
| `app/Filament/Resources/Feedbacks/FeedbackResource.php` | Filament resource |
| `app/Filament/Resources/Feedbacks/Pages/ListFeedbacks.php` | |
| `app/Filament/Resources/Feedbacks/Pages/ViewFeedback.php` | |
| `app/Filament/Resources/Feedbacks/Schemas/FeedbackForm.php` | |
| `app/Filament/Resources/Feedbacks/Tables/FeedbacksTable.php` | |
| `tests/Feature/Livewire/FeedbackButtonTest.php` | |
| `tests/Feature/Filament/FeedbackResourceTest.php` | |

**Módosított fájlok:**
- `resources/views/components/layouts/shop.blade.php`
- `resources/views/components/layouts/shop-auth.blade.php`
- `config/shop.php`

---

## Ellenőrzés

1. `php artisan migrate` — feedbacks tábla létrejön
2. Shop oldalon megjelenik a jobb-lenn lebegő gomb
3. Gombra kattintva modal nyílik, mobilon is rendesen
4. Bejelentkezett user esetén name/email előtölt
5. Submit → DB-ben rekord, email elment (Notification::fake-kel tesztelve)
6. Admin panelben `/admin/feedbacks` lista, státusz szűrő működik
7. `php artisan test tests/Feature/Livewire/FeedbackButtonTest.php`
8. `php artisan test tests/Feature/Filament/FeedbackResourceTest.php`
9. `vendor/bin/pint --dirty`
