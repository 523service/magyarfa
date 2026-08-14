Köszönjük a rendelését!

Rendelésszám: {{ $order->number }}
Dátum: {{ $order->created_at->format('Y. m. d.') }}

========================================
RENDELT TERMÉKEK
========================================

@foreach ($order->items as $item)
{{ $item->shop_product_id ? (\App\Models\Shop\Product::find($item->shop_product_id)?->name ?? 'Termék') : 'Termék' }}
@if ($item->secondary_qty && $item->secondary_unit)
Mennyiség: {{ number_format($item->secondary_qty, 0, ',', ' ') }} {{ $item->secondary_unit }} (= {{ $item->qty }} {{ $item->unit_name }}) - {{ number_format($item->unit_price * $item->qty, 0, ',', ' ') }} Ft
@else
Mennyiség: {{ $item->qty }}{{ $item->unit_name ? ' ' . $item->unit_name : '' }} - {{ number_format($item->unit_price * $item->qty, 0, ',', ' ') }} Ft
@endif

@endforeach
========================================
ÖSSZESÍTŐ
========================================

Részösszeg: {{ number_format($order->total_price - $order->shipping_price, 0, ',', ' ') }} Ft
@if ((int) $order->shipping_price === 0)
Szállítás: Ingyenes
@else
Szállítás: {{ number_format($order->shipping_price, 0, ',', ' ') }} Ft
@endif

Végösszeg: {{ number_format($order->total_price, 0, ',', ' ') }} Ft

========================================

Rendelését PDF formátumban is megtalálja a mellékletben.

Rendelés megtekintése: {{ $orderUrl }}

Kérdése van? Írjon nekünk: magyarszigeteles@gmail.com
