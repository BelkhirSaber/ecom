<?php

return [
    'enabled' => env('COD_ENABLED', false),

    'default_order_status' => env('COD_ORDER_STATUS', 'pending_cod'),
    'default_payment_status' => env('COD_PAYMENT_STATUS', 'pending_cod'),

    'zones' => [
        'france_idf' => [
            'label' => 'France - Île-de-France',
            'countries' => ['FR'],
            'postal_codes' => ['75*', '92*', '93*', '94*'],
        ],
    ],
];
