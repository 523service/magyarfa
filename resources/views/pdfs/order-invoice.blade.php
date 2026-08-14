<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendelés visszaigazolás - {{ $order->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-info {
            margin-bottom: 30px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: normal;
            color: #666;
        }

        .info-value {
            display: table-cell;
            font-weight: bold;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .items-table .text-right {
            text-align: right;
        }

        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 60%;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .total-label {
            display: table-cell;
            width: 60%;
            text-align: right;
            padding-right: 15px;
        }

        .total-value {
            display: table-cell;
            text-align: right;
            font-weight: normal;
        }

        .total-final {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .total-final .total-label,
        .total-final .total-value {
            font-size: 13pt;
            font-weight: bold;
        }

        .address-box {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .address-box h3 {
            font-size: 11pt;
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RENDELÉS VISSZAIGAZOLÁS</h1>
        <p>Köszönjük a rendelését!</p>
    </div>

    <div class="order-info">
        <div class="info-row">
            <span class="info-label">Rendelésszám:</span>
            <span class="info-value">{{ $order->number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dátum:</span>
            <span class="info-value">{{ $order->created_at->format('Y. m. d.') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fizetési mód:</span>
            <span class="info-value">
                @if ($order->payments->isNotEmpty())
                    {{ \App\Enums\PaymentMethod::from($order->payments->first()->method)->getLabel() }}
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Szállítási mód:</span>
            <span class="info-value">{{ \App\Enums\ShippingMethod::from($order->shipping_method)->getLabel() }}</span>
        </div>
    </div>

    <div class="section-title">Rendelt termékek</div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Termék</th>
                <th class="text-right">Mennyiség</th>
                <th class="text-right">Egységár</th>
                <th class="text-right">Összeg</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        @if ($item->shop_product_id)
                            {{ \App\Models\Shop\Product::find($item->shop_product_id)?->name ?? 'Termék' }}
                        @else
                            Termék
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($item->secondary_qty && $item->secondary_unit)
                            {{ number_format($item->secondary_qty, 0, ',', ' ') }} {{ $item->secondary_unit }}<br>
                            <small style="color:#888;">(= {{ $item->qty }} {{ $item->unit_name }})</small>
                        @else
                            {{ $item->qty }}{{ $item->unit_name ? ' ' . $item->unit_name : '' }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} Ft</td>
                    <td class="text-right">{{ number_format($item->unit_price * $item->qty, 0, ',', ' ') }} Ft</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span class="total-label">Részösszeg:</span>
            <span class="total-value">{{ number_format($order->total_price - $order->shipping_price, 0, ',', ' ') }} Ft</span>
        </div>
        <div class="total-row">
            <span class="total-label">Szállítás:</span>
            <span class="total-value">
                @if ((int) $order->shipping_price === 0)
                    Ingyenes
                @else
                    {{ number_format($order->shipping_price, 0, ',', ' ') }} Ft
                @endif
            </span>
        </div>
        <div class="total-row total-final">
            <span class="total-label">Végösszeg:</span>
            <span class="total-value">{{ number_format($order->total_price, 0, ',', ' ') }} Ft</span>
        </div>
    </div>

    @php
        $shippingAddr = $order->addresses->firstWhere('type', 'shipping');
        $billingAddr = $order->addresses->firstWhere('type', 'billing');
    @endphp

    @if ($shippingAddr)
        <div class="section-title">Szállítási cím</div>
        <div class="address-box">
            <p><strong>{{ $shippingAddr->name }}</strong></p>
            <p>{{ $shippingAddr->street }}</p>
            <p>{{ $shippingAddr->zip }} {{ $shippingAddr->city }}</p>
            @if ($shippingAddr->country)
                <p>{{ $shippingAddr->country }}</p>
            @endif
        </div>
    @endif

    @if ($billingAddr)
        <div class="section-title">Számlázási cím</div>
        <div class="address-box">
            <p><strong>{{ $billingAddr->billing_name }}</strong></p>
            @if ($billingAddr->tax_number)
                <p>Adószám: {{ $billingAddr->tax_number }}</p>
            @endif
            <p>{{ $billingAddr->street }}</p>
            <p>{{ $billingAddr->zip }} {{ $billingAddr->city }}</p>
            @if ($billingAddr->country)
                <p>{{ $billingAddr->country }}</p>
            @endif
        </div>
    @endif

    <div class="footer">
        <p>Kérdése van? Írjon nekünk: info@example.com</p>
    </div>
</body>
</html>
