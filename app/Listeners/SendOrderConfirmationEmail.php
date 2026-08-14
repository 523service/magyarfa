<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use App\Mail\StoreOrderNotification;
use App\Services\EmailTrackingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail
{
    public function __construct(
        protected EmailTrackingService $trackingService,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        if (! $order->email) {
            Log::warning("Order {$order->number} has no email, skipping.");

            return;
        }

        // Tracking record
        $emailSend = $this->trackingService->createTracking(
            $order,
            $order->email
        );

        // Send confirmation to customer
        Mail::to($order->email)
            ->send(new OrderConfirmation($order, $emailSend->tracking_token));

        // Send notification to store
        $storeEmail = config('shop.store_email');
        if ($storeEmail) {
            Mail::to($storeEmail)
                ->send(new StoreOrderNotification($order));
        }
    }
}
