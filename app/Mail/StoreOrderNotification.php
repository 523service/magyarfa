<?php

namespace App\Mail;

use App\Models\Shop\Order;
use App\Services\OrderPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreOrderNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    private string $pdfPath;

    public function __construct(
        public Order $order,
    ) {
        $this->order->load(['items.product', 'addresses', 'payments']);

        $pdfService = app(OrderPdfService::class);
        $this->pdfPath = $pdfService->generate($this->order);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Új rendelés érkezett: ' . $this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.store-order-notification',
            text: 'emails.store-order-notification-text',
            with: [
                'order' => $this->order,
                'orderUrl' => route('order.confirmation', ['number' => $this->order->number]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("rendeles-{$this->order->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
