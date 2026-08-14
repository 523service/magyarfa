<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új rendelés érkezett</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .header h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 10px 0;
        }

        .header p {
            color: #6b7280;
            margin: 0;
        }

        .alert-banner {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #1e40af;
            font-size: 14px;
            font-weight: 600;
        }

        .order-info {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }

        .order-number {
            font-size: 18px;
            color: #2563eb;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 25px 0 15px 0;
        }

        .items-list {
            margin-bottom: 20px;
        }

        .item {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .item-qty {
            color: #6b7280;
            font-size: 13px;
        }

        .item-price {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }

        .totals {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total-final {
            border-top: 2px solid #e5e7eb;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }

        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Új rendelés érkezett!</h1>
            <p>Ez egy automatikus értesítő a webáruház rendszerétől</p>
        </div>

        <div class="alert-banner">
            &#128276; Egy új rendelés vár feldolgozásra.
        </div>

        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Rendelésszám:</span>
                <span class="info-value order-number">{{ $order->number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dátum:</span>
                <span class="info-value">{{ $order->created_at->format('Y. m. d. H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Vásárló neve:</span>
                <span class="info-value">{{ $order->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Vásárló email:</span>
                <span class="info-value">{{ $order->email }}</span>
            </div>
            @if ($order->phone)
                <div class="info-row">
                    <span class="info-label">Telefonszám:</span>
                    <span class="info-value">{{ $order->phone }}</span>
                </div>
            @endif
            <div class="info-row">
                <span class="info-label">Végösszeg:</span>
                <span class="info-value">{{ number_format($order->total_price, 0, ',', ' ') }} Ft</span>
            </div>
        </div>

        <h2 class="section-title">Rendelt termékek</h2>

        <div class="items-list">
            @foreach ($order->items as $item)
                <div class="item">
                    <div style="display: flex; justify-content: space-between;">
                        <div>
                            <div class="item-name">
                                @if ($item->shop_product_id)
                                    {{ \App\Models\Shop\Product::find($item->shop_product_id)?->name ?? 'Termék' }}
                                @else
                                    Termék
                                @endif
                            </div>
                            <div class="item-qty">
                                @if ($item->secondary_qty && $item->secondary_unit)
                                    Mennyiség: {{ number_format($item->secondary_qty, 0, ',', ' ') }} {{ $item->secondary_unit }}
                                    (= {{ $item->qty }} {{ $item->unit_name }})
                                @else
                                    Mennyiség: {{ $item->qty }}{{ $item->unit_name ? ' ' . $item->unit_name : '' }}
                                @endif
                            </div>
                        </div>
                        <div class="item-price">
                            {{ number_format($item->unit_price * $item->qty, 0, ',', ' ') }} Ft
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="totals">
            <div class="total-row">
                <span>Részösszeg:</span>
                <span>{{ number_format($order->total_price - $order->shipping_price, 0, ',', ' ') }} Ft</span>
            </div>
            <div class="total-row">
                <span>Szállítás:</span>
                <span>
                    @if ((int) $order->shipping_price === 0)
                        Ingyenes
                    @else
                        {{ number_format($order->shipping_price, 0, ',', ' ') }} Ft
                    @endif
                </span>
            </div>
            <div class="total-row total-final">
                <span>Végösszeg:</span>
                <span>{{ number_format($order->total_price, 0, ',', ' ') }} Ft</span>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $orderUrl }}" class="button">Rendelés megtekintése</a>
        </div>

        <div class="footer">
            <p>A rendelés részleteit PDF mellékletben is megtalálja.</p>
        </div>
    </div>
</body>
</html>