<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Reads the Accept-Language header and sets the application locale.
 *
 * Supported:  Accept-Language: ar   → Arabic
 *             Accept-Language: en   → English (default)
 *
 * Clients can also use X-Locale: ar|en header as an alternative.
 */
class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Try Accept-Language first, then X-Locale, then default to 'en'
        $locale = $request->header('X-Locale')
            ?? $this->parseAcceptLanguage($request->header('Accept-Language', 'en'));

        $locale = in_array($locale, ['ar', 'en']) ? $locale : 'en';

        app()->setLocale($locale);

        $response = $next($request);

        // Echo back the resolved locale so clients know what was used
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function parseAcceptLanguage(string $header): string
    {
        // "ar-SA,ar;q=0.9,en;q=0.8" → "ar"
        $primary = explode(',', $header)[0];
        $lang    = explode('-', trim($primary))[0];
        return strtolower($lang);
    }
}
