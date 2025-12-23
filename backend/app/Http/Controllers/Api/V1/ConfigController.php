<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Return i18n configuration (available locales).
     */
    public function i18n(Request $request)
    {
        $locales = config('i18n.locales', []);
        $default = config('i18n.default', config('app.locale', 'fr'));
        $fallback = config('i18n.fallback', config('app.fallback_locale', $default));

        $current = $request->attributes->get('locale', app()->getLocale());

        return response()->json([
            'current' => $current,
            'default' => $default,
            'fallback' => $fallback,
            'available' => $locales,
        ]);
    }
}
