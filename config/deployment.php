<?php

return [
    'dump_binary' => env('DB_DUMP_BINARY', 'mysqldump'),
    // List only the actual reverse proxy IPs. Do not trust arbitrary clients.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
];
