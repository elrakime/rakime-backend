<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(['en', 'fr', 'ar']);

        if (App::getLocale() !== $locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
