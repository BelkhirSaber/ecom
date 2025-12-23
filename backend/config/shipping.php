<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des méthodes de livraison, zones géographiques et tarifs.
    | Les zones sont définies par pays, états/régions et codes postaux.
    |
    */

    'enabled' => env('SHIPPING_ENABLED', true),

    'default_currency' => env('SHIPPING_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Méthodes de livraison
    |--------------------------------------------------------------------------
    |
    | Chaque méthode peut avoir:
    | - label: Nom affiché
    | - description: Description de la méthode
    | - calculation_type: 'flat', 'weight_based', 'price_based', 'free'
    | - zones: Tableau des zones éligibles avec leurs tarifs
    |
    */

    'methods' => [
        'standard' => [
            'label' => 'Livraison Standard',
            'description' => 'Livraison en 3-5 jours ouvrés',
            'calculation_type' => 'flat',
            'enabled' => true,
            'zones' => [
                'france_metro' => [
                    'label' => 'France Métropolitaine',
                    'countries' => ['FR'],
                    'excluded_postal_codes' => ['20*'], // Corse
                    'price' => 5.90,
                    'free_above' => 50.00, // Gratuit au-dessus de 50€
                ],
                'france_corse' => [
                    'label' => 'Corse',
                    'countries' => ['FR'],
                    'postal_codes' => ['20*'],
                    'price' => 12.90,
                    'free_above' => 100.00,
                ],
                'europe' => [
                    'label' => 'Europe',
                    'countries' => ['BE', 'LU', 'DE', 'IT', 'ES', 'PT', 'NL', 'CH'],
                    'price' => 15.90,
                    'free_above' => 150.00,
                ],
            ],
        ],

        'express' => [
            'label' => 'Livraison Express',
            'description' => 'Livraison en 24-48h',
            'calculation_type' => 'flat',
            'enabled' => true,
            'zones' => [
                'france_metro' => [
                    'label' => 'France Métropolitaine',
                    'countries' => ['FR'],
                    'excluded_postal_codes' => ['20*'],
                    'price' => 12.90,
                    'free_above' => null, // Jamais gratuit
                ],
                'france_idf' => [
                    'label' => 'Île-de-France',
                    'countries' => ['FR'],
                    'postal_codes' => ['75*', '77*', '78*', '91*', '92*', '93*', '94*', '95*'],
                    'price' => 9.90,
                    'free_above' => 100.00,
                ],
            ],
        ],

        'weight_based' => [
            'label' => 'Livraison par poids',
            'description' => 'Tarif calculé selon le poids',
            'calculation_type' => 'weight_based',
            'enabled' => false,
            'zones' => [
                'france_metro' => [
                    'label' => 'France Métropolitaine',
                    'countries' => ['FR'],
                    'excluded_postal_codes' => ['20*'],
                    'weight_tiers' => [
                        ['max_weight' => 1.0, 'price' => 5.90],   // 0-1kg
                        ['max_weight' => 5.0, 'price' => 9.90],   // 1-5kg
                        ['max_weight' => 10.0, 'price' => 15.90], // 5-10kg
                        ['max_weight' => null, 'price' => 25.90], // 10kg+
                    ],
                ],
            ],
        ],

        'store_pickup' => [
            'label' => 'Retrait en magasin',
            'description' => 'Retrait gratuit en magasin sous 2h',
            'calculation_type' => 'free',
            'enabled' => true,
            'zones' => [
                'store_location' => [
                    'label' => 'Magasin Paris',
                    'countries' => ['FR'],
                    'price' => 0.00,
                    'free_above' => null,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paramètres de calcul
    |--------------------------------------------------------------------------
    */

    'weight_unit' => 'kg', // kg, g, lb

    'handling_fee' => env('SHIPPING_HANDLING_FEE', 0.00),

    /*
    |--------------------------------------------------------------------------
    | Restrictions
    |--------------------------------------------------------------------------
    */

    'max_weight' => 30.0, // kg
    'max_dimensions' => [
        'length' => 120, // cm
        'width' => 80,   // cm
        'height' => 80,  // cm
    ],
];
