<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next)
    {
        $configuredLocales = collect(config('i18n.locales', []));

        $available = $configuredLocales
            ->pluck('code')
            ->filter()
            ->map(fn ($code) => strtolower((string) $code))
            ->unique()
            ->values()
            ->all();

        $default = strtolower((string) config('i18n.default', config('app.locale', 'fr')));
        $fallback = strtolower((string) config('i18n.fallback', config('app.fallback_locale', $default)));

        $locale = $this->normalizeLocale($this->extractLocaleFromRequest($request)) ?? $default;
        if (! in_array($locale, $available, true)) {
            $locale = $default;
        }

        app()->setLocale($locale);

        $request->attributes->set('locale', $locale);
        $request->attributes->set('fallback_locale', $fallback);
        $request->attributes->set('available_locales', $available);

        return $next($request);
    }

    protected function extractLocaleFromRequest(Request $request): ?string
    {
        $candidates = [
            $request->query('lang'),
            $request->header('X-Locale'),
            $this->fromAcceptLanguage($request->header('Accept-Language')),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return strtolower($candidate);
            }
        }

        return null;
    }

    protected function fromAcceptLanguage(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        $parts = explode(',', $header);
        if (count($parts) === 0) {
            return null;
        }

        $primary = trim($parts[0]);
        if ($primary === '') {
            return null;
        }

        return strtolower(explode(';', $primary)[0] ?? $primary);
    }

    protected function normalizeLocale(?string $locale): ?string
    {
        if (! $locale) {
            return null;
        }

        $locale = strtolower($locale);

        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }

        if (str_contains($locale, '_')) {
            $locale = explode('_', $locale)[0];
        }

        return $locale;
    }
}
