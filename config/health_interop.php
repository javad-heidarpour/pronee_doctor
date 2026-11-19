<?php

return [
    'destinations' => [
        // Disabled until the official technical contract, credentials and
        // test-environment approval are supplied by the relevant authority.
        'sepas' => [
            'enabled' => (bool) env('SEPAS_ENABLED', false),
            'contract_approved' => (bool) env('SEPAS_CONTRACT_APPROVED', false),
            'url' => env('SEPAS_EXCHANGE_URL'),
            'token' => env('SEPAS_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
        'tamin' => [
            'enabled' => (bool) env('TAMIN_INSURANCE_ENABLED', false),
            'contract_approved' => (bool) env('TAMIN_CONTRACT_APPROVED', false),
            'url' => env('TAMIN_API_URL'),
            'token' => env('TAMIN_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
        'salamat' => [
            'enabled' => (bool) env('SALAMAT_INSURANCE_ENABLED', false),
            'contract_approved' => (bool) env('SALAMAT_CONTRACT_APPROVED', false),
            'url' => env('SALAMAT_API_URL'),
            'token' => env('SALAMAT_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
    ],
];
