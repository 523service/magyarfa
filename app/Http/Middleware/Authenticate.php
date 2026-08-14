<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // If accessing Filament admin routes, redirect to Filament login
        if ($request->is('admin') || $request->is('admin/*')) {
            return Filament::getLoginUrl();
        }

        // Otherwise, redirect to Breeze login
        return route('login');
    }
}
