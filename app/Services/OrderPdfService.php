<?php

namespace App\Services;

use App\Models\Shop\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPdfService
{
    public function generate(Order $order): string
    {
        $order->load(['items.product', 'addresses', 'payments']);

        $pdf = Pdf::loadView('pdfs.order-invoice', [
            'order' => $order,
        ]);

        $filename = "rendeles-{$order->number}.pdf";
        $path = storage_path("app/pdf/{$filename}");

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }
}
