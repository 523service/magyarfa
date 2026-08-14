<?php

namespace App\Http\Controllers;

use App\Services\EmailTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function __construct(
        protected EmailTrackingService $trackingService
    ) {}

    public function pixel(string $token): Response
    {
        $this->trackingService->recordOpen($token);

        // Return a 1x1 transparent PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function click(Request $request, string $token): RedirectResponse
    {
        $url = base64_decode($request->query('url', ''));

        if (! $url) {
            abort(404);
        }

        $this->trackingService->recordClick($token, $url, $request);

        return redirect($url);
    }
}
