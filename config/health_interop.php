<?php

return [
    'destinations' => [
        // Disabled until the official technical contract, credentials and
        // test-environment approval are supplied by the relevant authority.
        'sepas' => [
            'enabled' => (bool) env('SEPAS_ENABLED', false),
            'contract_approved' => (bool) env('SEPAS_CONTRACT_APPROVED', false),
            'url' => env('SEPAS_EXCHANGE_URL'),
            // فهرست دامنه‌های دقیق و مورد تأیید؛ مقصد بدون این فهرست فعال نمی‌شود.
            'allowed_hosts' => array_filter(array_map('trim', explode(',', (string) env('SEPAS_ALLOWED_HOSTS', '')))),
            'token' => env('SEPAS_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
        'tamin' => [
            'enabled' => (bool) env('TAMIN_INSURANCE_ENABLED', false),
            'contract_approved' => (bool) env('TAMIN_CONTRACT_APPROVED', false),
            'url' => env('TAMIN_API_URL'),
            'allowed_hosts' => array_filter(array_map('trim', explode(',', (string) env('TAMIN_ALLOWED_HOSTS', '')))),
            'token' => env('TAMIN_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
        'salamat' => [
            'enabled' => (bool) env('SALAMAT_INSURANCE_ENABLED', false),
            'contract_approved' => (bool) env('SALAMAT_CONTRACT_APPROVED', false),
            'url' => env('SALAMAT_API_URL'),
            'allowed_hosts' => array_filter(array_map('trim', explode(',', (string) env('SALAMAT_ALLOWED_HOSTS', '')))),
            'token' => env('SALAMAT_API_TOKEN'),
            'headers' => [],
            'connect_timeout' => 5,
            'timeout' => 20,
        ],
    ],
];
