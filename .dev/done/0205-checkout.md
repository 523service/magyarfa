# Checkout Page — Implementation Plan

## Requirements Summary
- **Single-page** Livewire checkout at `/rendeles` with step navigation (Auth → Cím → Szállítás → Fizetés)
- **Guest flow**: email + name required inline, no registration mandatory
- **Login/Register on checkout**: available as tabs in step 1
- **Logged-in flow**: select saved shipping + billing addresses, or add new inline
- **Billing address**: "Same as shipping" checkbox; if unchecked, separate form
- **Shipping**: Futárral kiszállítás (configurable price) + Átvétel a telephelyen (free). Free shipping above configurable threshold
- **Payment**: Előre utalás (bank transfer) + Fizetés átvételkor (COD)
- **Config**: `config/shipping.php` — prices editable, later from admin too
- **After order**: Confirmation page (`/rendeles/{number}`) with order summary
- **Language**: Hungarian throughout

---

## Existing Infrastructure (reuse these)
| What                   | Where                                      | Notes                                                                                                                                                                          |
|------------------------|--------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| CartService            | `app/Services/CartService.php`             | `getContent()`, `getSubTotal()`, `getItemCount()`, `clear()`                                                                                                                   |
| User addresses         | `app/Models/User.php:74-101`               | `shippingAddresses()`, `billingAddresses()`, `defaultShippingAddress()`, `defaultBillingAddress()` — MorphToMany with pivot: type, is_default, label, billing_name, tax_number |
| AddressManager pattern | `app/Livewire/Profile/AddressManager.php`  | Field names (street, zip, city, state, country), `#[Validate]` rules, card layout                                                                                              |
| Shop layout            | `components.layouts.shop`                  | Used by CartPage, all shop pages                                                                                                                                               |
| Order model            | `app/Models/Shop/Order.php`                | `address()` morphOne used by Filament admin — DO NOT change                                                                                                                    |
| OrderFactory           | `database/factories/Shop/OrderFactory.php` | Uses `$order->address()->save(...)` — must keep working                                                                                                                        |
| OrderStatus enum       | `app/Enums/OrderStatus.php`                | Pattern: implements HasColor, HasIcon, HasLabel                                                                                                                                |
| Livewire DI pattern    | `app/Livewire/Shop/Cart/CartPage.php`      | Services injected as method params, NOT constructor                                                                                                                            |

---

## Critical Constraint: `shop_order_addresses` table
- **Table EXISTS** — created in `database/migrations/2021_12_13_184540_create_addresses_table.php`
- Current columns: id, addressable_type, addressable_id, country, street, city, state, zip, timestamps
- Uses morph columns (`addressable` morph name)
- `Order::address()` is `morphOne(OrderAddress::class, 'addressable')` — used by Filament `AddressForm::make('address')` in `app/Filament/Resources/Shop/Orders/Schemas/OrderForm.php:119`
- **KEEP `address()` morphOne intact.** Add NEW `addresses()` morphMany alongside it.
- Need migration to add: `type`, `name`, `billing_name`, `tax_number` columns

---

## Files to CREATE

### 1. `config/shipping.php`
```php
return [
    'courier_price'  => (int) env('SHIPPING_COURIER_PRICE', 990),
    'free_threshold' => (int) env('SHIPPING_FREE_THRESHOLD', 15000),
    'pickup_price'   => 0,
];
```

### 2. `app/Enums/ShippingMethod.php`
Backed enum: `Courier = 'courier'`, `Pickup = 'pickup'`.
Implements HasColor, HasIcon, HasLabel (same pattern as OrderStatus).
Labels: "Futárral kiszállítás", "Átvétel a telephelyen".

### 3. `app/Enums/PaymentMethod.php`
Backed enum: `BankTransfer = 'bank_transfer'`, `CashOnDelivery = 'cash_on_delivery'`.
Same interfaces. Labels: "Előre utalás", "Fizetés átvételkor".

### 4. Migration: add columns to `shop_order_addresses`
```
type            string  default 'shipping'   // shipping | billing
name            string  nullable            // recipient name
billing_name    string  nullable            // invoice name (billing only)
tax_number      string  nullable            // adószám (billing only)
```
Existing seeded data gets `type = 'shipping'` by default — safe.

### 5. `app/Services/ShippingService.php`
Stateless. Reads `config('shipping.*')`.
```
calculatePrice(ShippingMethod $method, float $subtotal): int
isFreeShipping(ShippingMethod $method, float $subtotal): bool
getCourierBasePrice(): int
getFreeThreshold(): int
```

### 6. `app/Services/CheckoutService.php`
Transactional order creation. Constructor-injected CartService + ShippingService (standard Laravel DI — this is a plain service, not Livewire).
```
placeOrder(array $data): Order
  - Creates Order (status=new, currency=huf)
  - Creates OrderItems from cart (with sort column)
  - Creates OrderAddress records (shipping + billing via addresses() morphMany)
  - Creates Payment record (provider='internal', method=enum value)
  - Clears cart
  - Returns Order

generateOrderNumber(): string  — 'OR' + Str::random(8)
```
Payment amount = subtotal + shipping. `shop_orders.total_price` = same.

### 7. `app/Livewire/Shop/Checkout/CheckoutPage.php`
Single-page component with `$step` state: `auth` → `address` → `shipping` → `payment`.

**State properties by step:**
- Auth: `$authTab` (guest/login/register), guest email/name, login email/password, register fields, `$authError`
- Address: `$selectedShippingAddressId`, `$selectedBillingAddressId`, `$billingIsSameAsShipping` (bool, default true), `$addingNewShippingAddress`, `$addingNewBillingAddress`, inline address fields for BOTH shipping and billing (prefix: `$shipping*` and `$billing*`)
- Shipping: `$shippingMethod` (string, default 'courier') — `updatedShippingMethod()` lifecycle hook recalculates price live
- Payment: `$paymentMethod` (string, default 'bank_transfer'), `$notes`
- Computed: `$subTotal`, `$shippingPrice`, `$totalPrice`, `$itemCount`

**Key methods:**
- `mount()`: redirect if cart empty; if Auth::check() → skip to 'address', preselect default addresses
- `continueAsGuest()`: validate guest email+name → step='address'
- `loginAndContinue()`: Auth::attempt() → step='address', init addresses
- `registerAndContinue()`: User::create(), Auth::login() → step='address'
- `continueToShipping()`: validate address fields (guest: shipping inline; logged-in with saved: load into fields) → step='shipping'
- `continueToPayment()`: calculate shipping price → step='payment'
- `placeOrder()`: call CheckoutService → redirect to confirmation
- `back(string $step)`: navigate back

**Validation:** Use `#[Validate]` attributes + `validateOnly()` per field at each step transition (multi-step single-component pattern — see AddressManager).

**Services:** injected as method parameters (Livewire DI pattern from CartPage).

### 8. `app/Livewire/Shop/Checkout/OrderConfirmation.php`
Simple display component. `mount(string $number)`: loads Order with `items` and `payments` eager-loaded. `firstOrFail()` → 404 for invalid number.

### 9. `resources/views/livewire/shop/checkout/checkout-page.blade.php`
Structure:
```
<div>  <!-- Livewire single root -->
  <!-- Progress bar: 4 steps, active = accent color -->

  <div class="lg:grid lg:grid-cols-3 lg:gap-8">
    <!-- LEFT: step content (lg:col-span-2) -->

    <!-- Step 1: Auth (wire:show="step === 'auth'") -->
    <!--   Tab nav: Bejelentkezés | Regisztrálás | Vendégként tovább -->
    <!--   Login form / Register form / Guest form (tab-switched) -->

    <!-- Step 2: Address (wire:show="step === 'address'") -->
    <!--   === Szállítási cím === -->
    <!--   Logged-in: saved address cards (selectable) + "Új cím" btn -->
    <!--   Guest OR addingNew: inline form (street, zip, city, state, country) -->
    <!--   === Számlázási cím === -->
    <!--   Checkbox: "Megegyezik a szállítási címmel" (wire:model="billingIsSameAsShipping") -->
    <!--   If !billingIsSameAsShipping: same card/form pattern as shipping -->
    <!--   "Tovább" button -->

    <!-- Step 3: Shipping (wire:show="step === 'shipping'") -->
    <!--   Radio cards: Courier (price or "Ingyenes") | Pickup (Ingyenes) -->
    <!--   If courier selected + below threshold: progress bar "X Ft-ig ingyenes szállítás" -->
    <!--   "Tovább" button -->

    <!-- Step 4: Payment (wire:show="step === 'payment'") -->
    <!--   Radio cards: Bank transfer | COD -->
    <!--   Notes textarea -->
    <!--   Order review: items table, shipping, billing addr, total -->
    <!--   "Megrendelés leadása" button (wire:click="placeOrder") -->
    <!--   wire:loading states on the button -->

    <!-- RIGHT: Order summary sidebar (lg:col-span-1, sticky) -->
    <!--   Cart items list (from $cartItems) -->
    <!--   Subtotal -->
    <!--   Shipping cost (dynamic) -->
    <!--   Total -->
    <!--   "Az ár tartalmazza az ÁFA-t" -->
  </div>
</div>
```
**Styling conventions:**
- Input fields: match `login.blade.php` pattern (border, focus ring with accent)
- Radio cards: bordered card, selected = `ring-2 ring-[var(--accent)] ring-offset-1`
- Tab nav: border-bottom, active = `border-[var(--accent)] text-[var(--accent)]`
- Number format: `number_format($val, 0, ',', ' ') . ' Ft'`
- Buttons: `bg-[var(--accent)] text-white ... hover:bg-[var(--accent-hover)]`
- Use `wire:show` (not `@if`) for steps — preserves state across navigation

### 10. `resources/views/livewire/shop/checkout/order-confirmation.blade.php`
- Green checkmark icon
- "Rendelés sikeres!" heading
- Order number (accent color, large)
- Payment method label
- Items summary with totals
- Shipping + total
- "Vissza a főoldalra" button → `route('home')`

---

## Files to MODIFY

### 11. `routes/web.php`
Add (Hungarian slugs, consistent with existing):
```php
Route::get('/penztár', CheckoutPage::class)->name('checkout.index');
Route::get('/rendelés/{number}', OrderConfirmation::class)->name('order.confirmation');
```
No auth middleware — guests can checkout. Order number is the access token.

### 12. `app/Models/Shop/Order.php`
Add `addresses()` MorphMany alongside existing `address()` morphOne:
```php
public function addresses(): MorphMany
{
    return $this->morphMany(OrderAddress::class, 'addressable');
}
```
Keep `address()` morphOne UNCHANGED (Filament uses it).

### 13. `app/Models/Shop/OrderAddress.php`
Add fillable array:
```php
protected $fillable = [
    'country', 'street', 'city', 'state', 'zip',
    'type', 'name', 'billing_name', 'tax_number',
];
```
Keep `morphTo` relationship unchanged.

### 14. `app/Models/Shop/OrderItem.php`
Add fillable array:
```php
protected $fillable = [
    'shop_order_id', 'shop_product_id', 'qty', 'unit_price', 'sort',
];
```

### 15. `resources/views/livewire/shop/cart/cart-page.blade.php` (lines 186-191)
Change checkout `<button>` → `<a href="{{ route('checkout.index') }}">` with `block` and `text-center` added to classes.

---

## Tests to CREATE

### 16. `tests/Feature/ShippingServiceTest.php`
- courier price returns configured default (990)
- pickup price is always 0
- courier is free at and above threshold
- courier is NOT free below threshold
- getFreeThreshold / getCourierBasePrice return config values

### 17. `tests/Feature/CheckoutServiceTest.php`
- place order creates Order with status=new, currency=huf
- order items match cart (qty, unit_price, sort order)
- shipping + billing OrderAddress records created
- billing same as shipping creates identical billing record
- Payment record created with correct method + amount
- cart cleared after successful order
- total_price = subtotal + shipping_price
- order number is unique across multiple orders

### 18. `tests/Feature/CheckoutPageTest.php`
- empty cart redirects to /kosar
- guests see auth step; logged-in users skip to address
- guest continueAsGuest requires email + name (validation)
- login with valid credentials advances to address
- login with bad password shows error message
- register creates user and advances to address
- logged-in user with default address: preselected
- address step validates street/zip/city for guests
- shipping price updates live on method change
- free shipping shows when subtotal >= threshold
- full happy path: guest → address → shipping → payment → order created → redirect
- full happy path: logged-in → address (saved) → shipping → payment → order created
- order confirmation page shows order data
- order confirmation 404 for invalid number

---

## Execution Order
1. `config/shipping.php`
2. Enums: `ShippingMethod`, `PaymentMethod`
3. Migration (add columns to shop_order_addresses)
4. Model changes: `Order`, `OrderAddress`, `OrderItem`
5. `ShippingService`
6. `CheckoutService`
7. Routes in `web.php`
8. `CheckoutPage` Livewire component
9. `OrderConfirmation` Livewire component
10. Views: checkout-page.blade.php, order-confirmation.blade.php
11. Cart button wiring (cart-page.blade.php)
12. Tests (run after each group: ShippingService → CheckoutService → CheckoutPage)
13. Run `vendor/bin/pint --dirty` before finalizing

---

## Verification
- `php artisan migrate` — verify no errors
- `php artisan test tests/Feature/ShippingServiceTest.php`
- `php artisan test tests/Feature/CheckoutServiceTest.php`
- `php artisan test tests/Feature/CheckoutPageTest.php`
- Manual: add items to cart → /pénztár → walk through all steps as guest and logged-in user
- Manual: verify Filament admin OrderResource still works (address() morphOne not broken)
