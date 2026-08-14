<?php

namespace App\Services;

use App\Models\EmailSend;
use App\Models\Shop\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailTrackingService
{
    public function createTracking(Order $order, string $email): EmailSend
    {
        return EmailSend::create([
            'shop_order_id' => $order->id,
            'recipient_email' => $email,
            'subject' => 'Rendelés visszaigazolás: ' . $order->number,
            'tracking_token' => Str::random(32),
            'sent_at' => now(),
        ]);
    }

    public function getTrackingPixelUrl(string $token): string
    {
        return route('email.pixel', ['token' => $token]);
    }

    public function getTrackedLinkUrl(string $token, string $targetUrl): string
    {
        return route('email.click', [
            'token' => $token,
            'url' => base64_encode($targetUrl),
        ]);
    }

    public function recordOpen(string $token): void
    {
        $emailSend = EmailSend::where('tracking_token', $token)->first();

        if (! $emailSend) {
            return;
        }

        if ($emailSend->opened_at === null) {
            $emailSend->opened_at = now();
            $emailSend->save();
        }

        $emailSend->increment('open_count');
    }

    public function recordClick(string $token, string $url, Request $request): void
    {
        $emailSend = EmailSend::where('tracking_token', $token)->first();

        if (! $emailSend) {
            return;
        }

        $emailSend->clicks()->create([
            'url' => $url,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'clicked_at' => now(),
        ]);

        $emailSend->increment('click_count');
    }
}
