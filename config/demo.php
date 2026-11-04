<?php

return [
    'content_enabled' => env('APP_ENV') !== 'production' && env('DEMO_CONTENT_ENABLED', false),
    'enabled' => env('APP_ENV') !== 'production' && env('DEMO_ACCOUNTS_ENABLED', false),
];
