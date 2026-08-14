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

class OrderConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    private string $pdfPath;

    public function __construct(
        public Order $order,
        public string $trackingToken
    ) {
        $this->order->load(['items.product', 'addresses', 'payments']);

        // Generate PDF
        $pdfService = app(OrderPdfService::class);
        $this->pdfPath = $pdfService->generate($this->order);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendelés visszaigazolás: ' . $this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            text: 'emails.order-confirmation-text',
            with: [
                'order' => $this->order,
                'trackingPixelUrl' => route('email.pixel', ['token' => $this->trackingToken]),
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
