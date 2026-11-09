<?php

return [
    'api_url' => env('ACCURATE_API_URL', 'https://account.accurate.id'),
    'api_token_url' => env('ACCURATE_API_TOKEN_URL', 'https://zeus.accurate.id'),
    'access_token' => env('ACCURATE_ACCESS_TOKEN'),
    'signature_secret' => env('ACCURATE_SIGNATURE_SECRET'),
    'timezone' => env('ACCURATE_TIMEZONE', 'Asia/Jakarta'),
    'default_database_id' => env('ACCURATE_DEFAULT_DB_ID'),
    'timeout' => (int) env('ACCURATE_TIMEOUT', 30),
    'cache_ttl' => (int) env('ACCURATE_CACHE_TTL', 180),
    'session_ttl' => (int) env('ACCURATE_SESSION_TTL', 1800),

    'endpoints' => [
        'databases' => '/api/db-list.do',
        'open_database' => '/api/open-db.do',
        'purchase-order' => [
            'list' => '/accurate/api/purchase-order/list.do',
            'detail' => '/accurate/api/purchase-order/detail.do',
        ],
        'purchase-requisition' => [
            'list' => '/accurate/api/purchase-requisition/list.do',
            'detail' => '/accurate/api/purchase-requisition/detail.do',
        ],
        'receive-item' => [
            'detail' => '/accurate/api/receive-item/detail.do',
        ],
        'item-transfer' => [
            'list' => '/accurate/api/item-transfer/list.do',
            'detail' => '/accurate/api/item-transfer/detail.do',
        ],
    ],
];
