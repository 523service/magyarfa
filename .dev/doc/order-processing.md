# Order Processing Flow Documentation

## Overview

This document describes the complete order processing flow in the **szigeteles** webshop application. The system handles guest and authenticated user checkout, multi-step order creation, payment tracking, and email notifications with PDF attachments.

## High-Level Flow

```
┌─────────────────┐
│  Shopping Cart  │
│  (CartService)  │
└────────┬────────┘
         │
         v
┌─────────────────────────────────────────────────────────────────┐
│                    CHECKOUT PROCESS                             │
│                   (CheckoutPage Livewire)                       │
│                                                                 │
│  Step 1: AUTH                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                     │
│  │  Guest   │  │  Login   │  │ Register │                     │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘                     │
│       └─────────────┼─────────────┘                            │
│                     v                                           │
│  Step 2: ADDRESS                                                │
│  ┌───────────────────────────────────────┐                     │
│  │ Shipping Address  │  Billing Address  │                     │
│  │ (new or saved)    │  (same or diff)   │                     │
│  └───────────────────┬───────────────────┘                     │
│                      v                                          │
│  Step 3: SHIPPING                                               │
│  ┌─────────────────────────────────┐                           │
│  │  ShippingMethod Selection       │                           │
│  │  - Courier (with free threshold)│                           │
│  │  - Pickup (free)                │                           │
│  │  (ShippingService calculates)   │                           │
│  └───────────────┬─────────────────┘                           │
│                  v                                              │
│  Step 4: PAYMENT                                                │
│  ┌─────────────────────────────────┐                           │
│  │  PaymentMethod Selection        │                           │
│  │  - Bank Transfer                │                           │
│  │  - Cash on Delivery             │                           │
│  │  + Optional Notes               │                           │
│  └───────────────┬─────────────────┘                           │
│                  v                                              │
│         [ Place Order Button ]                                 │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      v
         ┌────────────────────────┐
         │   CheckoutService      │
         │   placeOrder()         │
         │  (DB Transaction)      │
         └────────┬───────────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
    v             v             v
┌────────┐  ┌──────────┐  ┌──────────┐
│ Order  │  │ Items    │  │Addresses │
│ Created│  │ Created  │  │ Created  │
│ Status:│  │ (from    │  │(shipping │
│  NEW   │  │  cart)   │  │ +billing)│
└────┬───┘  └──────────┘  └──────────┘
     │
     v
┌──────────┐
│ Payment  │
│ Record   │
│ Created  │
└────┬─────┘
     │
     v
┌────────────┐
│ Cart       │
│ Cleared    │
└────┬───────┘
     │
     v
┌──────────────────┐
│ OrderPlaced      │
│ Event Fired      │
└────┬─────────────┘
     │
     v
┌────────────────────────────────────────────────────────────┐
│         SendOrderConfirmationEmail Listener                │
│                                                            │
│  1. Create EmailTracking record                           │
│     (EmailTrackingService)                                │
│                                                            │
│  2. Generate Order PDF                                    │
│     (OrderPdfService via OrderConfirmation Mailable)      │
│                                                            │
│  3. Send Email with PDF attachment                        │
│     - HTML view: emails.order-confirmation                │
│     - Text view: emails.order-confirmation-text           │
│     - Tracking pixel URL included                         │
│     - PDF attached: rendeles-{ORDER_NUMBER}.pdf           │
└────────────────────┬───────────────────────────────────────┘
                     │
                     v
         ┌───────────────────────┐
         │  Redirect to          │
         │  OrderConfirmation    │
         │  Page (Livewire)      │
         │  /rendeles/           │
         │  megerosites/{number} │
         └───────────────────────┘
```

## Detailed Component Breakdown

### 1. CheckoutPage (Livewire Component)

**Location:** `app/Livewire/Shop/Checkout/CheckoutPage.php`

**Route:** `/rendeles`

#### Four-Step Process:

**Step 1: Authentication (`auth`)**
- **Guest Flow:** Collect name and email
- **Login Flow:** Authenticate existing user
- **Register Flow:** Create new user account with password

**Step 2: Address Collection (`address`)**
- **Shipping Address:**
  - Logged-in users can select saved addresses or add new
  - Guests enter address manually
  - Fields: street, city, zip, state (optional), country (default: HU)

- **Billing Address:**
  - Can be same as shipping (default)
  - Or separate with additional fields: billing_name, tax_number

**Step 3: Shipping Method Selection (`shipping`)**
- Options via `ShippingMethod` enum:
  - `Courier`: Paid delivery with free shipping threshold
  - `Pickup`: Free pickup at location
- `ShippingService` calculates real-time costs based on cart subtotal

**Step 4: Payment Method & Review (`payment`)**
- Options via `PaymentMethod` enum:
  - `BankTransfer`: Pay in advance
  - `CashOnDelivery`: Pay on delivery
- Optional order notes
- Final price display (subtotal + shipping)
- **Place Order** button triggers `placeOrder()` method

### 2. CheckoutService

**Location:** `app/Services/CheckoutService.php`

**Core Method:** `placeOrder(array $data): Order`

#### Process (DB Transaction):

1. **Calculate Totals:**
   ```
   subtotal = CartService::getSubTotal()
   shippingPrice = ShippingService::calculatePrice(method, subtotal)
   totalPrice = subtotal + shippingPrice
   ```

2. **Create Order Record:**
   - Generate unique order number: `OR{RANDOM8}` (e.g., `ORAX7BN9K2`)
   - Status: `OrderStatus::New`
   - Currency: `huf`
   - Store total_price, shipping_price, shipping_method
   - Store email and optional notes

3. **Create Order Items** (`createOrderItems`):
   - Loop through cart items
   - Create `OrderItem` for each with:
     - `shop_order_id`, `shop_product_id`
     - `qty`, `unit_price`
     - `sort` (sequential)

4. **Create Order Addresses** (`createOrderAddresses`):
   - **Shipping Address** (type: `shipping`):
     - name, street, city, zip, state, country
   - **Billing Address** (type: `billing`):
     - Same fields + billing_name, tax_number

5. **Create Payment Record** (`createPayment`):
   - Reference: `{ORDER_NUMBER}-{METHOD}` (e.g., `ORAX7BN9K2-bank_transfer`)
   - Provider: `internal`
   - Method: payment method value
   - Amount: total price
   - Currency: `huf`

6. **Clear Cart:**
   ```php
   CartService::clear()
   ```

7. **Fire Event:**
   ```php
   event(new OrderPlaced($order))
   ```

8. **Return Order** (with all relationships loaded)

### 3. ShippingService

**Location:** `app/Services/ShippingService.php`

**Key Methods:**

- `calculatePrice(ShippingMethod $method, float $subtotal): int`
  - `Pickup`: Always 0 Ft
  - `Courier`: Base price or 0 if subtotal ≥ free threshold

- `isFreeShipping(ShippingMethod $method, float $subtotal): bool`

**Configuration:** `config/shipping.php`
- `courier_price`: Base courier delivery cost (Ft)
- `free_threshold`: Subtotal amount for free shipping (Ft)

### 4. OrderPlaced Event & Listener

**Event:** `app/Events/OrderPlaced.php`
- Simple event carrying the `Order` model

**Listener:** `app/Listeners/SendOrderConfirmationEmail.php`

**Process:**
1. Extract order email (skip if missing)
2. Create email tracking record via `EmailTrackingService`
   - Stores: order_id, email, tracking_token, sent_at
3. Send email synchronously via `Mail::to($email)->send()`

### 5. OrderConfirmation Mailable

**Location:** `app/Mail/OrderConfirmation.php`

**Constructor Actions:**
- Eager-load relationships: `items.product`, `addresses`, `payments`
- Generate PDF invoice via `OrderPdfService::generate($order)`
- Store temporary PDF path

**Email Contents:**
- **Subject:** `Rendelés visszaigazolás: {ORDER_NUMBER}`
- **HTML View:** `emails.order-confirmation`
- **Text View:** `emails.order-confirmation-text`
- **Variables:**
  - `$order`: Full order model
  - `$trackingPixelUrl`: For email open tracking
  - `$orderUrl`: Link to order confirmation page
- **Attachment:** PDF invoice (`rendeles-{ORDER_NUMBER}.pdf`)

### 6. OrderConfirmation Page

**Location:** `app/Livewire/Shop/Checkout/OrderConfirmation.php`

**Route:** `/rendeles/megerosites/{number}`

**Purpose:**
- Display order confirmation to customer
- Show order details, items, addresses, payment info
- Loads order by unique order number (not ID for security)
- Eager-loads: `items`, `payments`, `addresses`

## Database Schema

### Core Tables:

**`shop_orders`**
- `id`, `number` (unique), `email`
- `status` (enum: new, processing, shipped, delivered, cancelled)
- `total_price`, `shipping_price`, `currency`
- `shipping_method`, `notes`
- `shop_customer_id` (nullable - guest orders don't have this)
- `created_at`, `updated_at`, `deleted_at`

**`shop_order_items`**
- `id`, `shop_order_id`, `shop_product_id`
- `qty`, `unit_price`
- `sort`
- `created_at`, `updated_at`

**`shop_order_addresses`** (polymorphic)
- `id`, `addressable_type`, `addressable_id`
- `type` (shipping | billing)
- `name`, `street`, `city`, `zip`, `state`, `country`
- `billing_name`, `tax_number` (billing-specific)
- `created_at`, `updated_at`

**`shop_payments`**
- `id`, `order_id` (FK to `shop_orders.id`)
- `reference`, `provider`, `method`
- `amount`, `currency`
- `created_at`, `updated_at`

**`email_sends`** (tracking)
- `id`, `shop_order_id`
- `email`, `tracking_token`
- `sent_at`, `opened_at`, `clicked_at`

### Key Relationships:

```
Order (1) ──< (N) OrderItem ──> (1) Product
Order (1) ──< (N) OrderAddress (polymorphic)
Order (1) ──< (N) Payment
Order (1) ──< (N) EmailSend
Order (N) ──> (1) Customer (nullable, only for logged-in users)
```

## Enums

### OrderStatus

**Values:**
- `New` → Blue (info) - Sparkles icon
- `Processing` → Yellow (warning) - Arrow path icon
- `Shipped` → Green (success) - Truck icon
- `Delivered` → Green (success) - Check badge icon
- `Cancelled` → Red (danger) - X circle icon

### ShippingMethod

**Values:**
- `Courier` → "Futárral kiszállítás" (Courier delivery)
- `Pickup` → "Átvétel a telephelyen" (Pickup at location)

### PaymentMethod

**Values:**
- `BankTransfer` → "Előre utalás" (Pay in advance)
- `CashOnDelivery` → "Fizetés átvételkor" (Pay on delivery)

## Error Handling & Edge Cases

1. **Empty Cart:**
   - CheckoutPage `mount()` redirects to cart if empty
   - `placeOrder()` double-checks before processing

2. **Transaction Safety:**
   - All order creation steps wrapped in `DB::transaction()`
   - Rollback on any failure (order, items, addresses, payment)

3. **Guest vs Authenticated:**
   - Guests: no customer_id, can't save addresses
   - Authenticated: can reuse saved addresses, order linked to customer

4. **Email Failures:**
   - Listener catches errors but doesn't block order creation
   - Order is still created even if email fails
   - Tracking records failures for admin review

5. **PDF Generation:**
   - Generated in mailable constructor
   - Temporary file stored, cleaned up after send
   - If PDF fails, email still sends (without attachment)

## Testing Coverage

**Test Files:**
- `tests/Unit/ShippingServiceTest.php` (8 tests)
- `tests/Unit/CheckoutServiceTest.php` (13 tests)
- `tests/Feature/CheckoutPageTest.php` (19 tests)

**Total:** 40 passing tests cover:
- Shipping cost calculation
- Order creation process
- Multi-step checkout flow
- Auth scenarios (guest, login, register)
- Address handling
- Email sending and PDF attachment

## Configuration

**Config Files:**
- `config/shipping.php` - Shipping prices and thresholds
- `config/mail.php` - Email driver configuration
- `config/filesystems.php` - PDF storage configuration

## Routes

```php
// Checkout
Route::get('/rendeles', CheckoutPage::class)->name('checkout');

// Order confirmation
Route::get('/rendeles/megerosites/{number}', OrderConfirmation::class)
    ->name('order.confirmation');

// Email tracking pixel
Route::get('/email/pixel/{token}', [EmailController::class, 'pixel'])
    ->name('email.pixel');
```

## Notes for Developers

1. **Never skip the transaction** in `CheckoutService::placeOrder()` - it ensures data consistency

2. **Always eager-load relationships** when displaying order details to avoid N+1 queries

3. **Use order number (not ID)** in public-facing URLs for security

4. **Cart clearing is final** - happens inside the transaction, so if transaction rolls back, cart persists

5. **Email tracking is async-safe** - listener creates tracking record before sending, so token is always valid

6. **PDF generation is synchronous** - happens during mailable construction, not queued (consider queuing for production)

7. **Shipping price is cached** - calculated once in checkout, stored with order (not recalculated later)

8. **Payment records are placeholders** - no actual payment processing integration yet, records are for tracking only

## Future Enhancements

- Queue email sending for better performance
- Implement real payment gateway integration
- Add order status change notifications
- Implement order tracking for customers
- Add admin notification on new orders
- Support for discount codes/coupons
- Multi-currency support beyond HUF
- Advanced shipping options (express, scheduled delivery)
