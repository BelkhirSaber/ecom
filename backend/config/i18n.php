<?php

return [
    'default' => env('APP_LOCALE', config('app.locale', 'fr')),
    'fallback' => env('APP_FALLBACK_LOCALE', config('app.fallback_locale', 'fr')),
    'locales' => [
        [
            'code' => 'fr',
            'label' => 'Français',
            'native_label' => 'Français',
            'dir' => 'ltr',
        ],
        [
            'code' => 'en',
            'label' => 'English',
            'native_label' => 'English',
            'dir' => 'ltr',
        ],
        [
            'code' => 'ar',
            'label' => 'Arabic',
            'native_label' => 'العربية',
            'dir' => 'rtl',
        ],
    ],
];
