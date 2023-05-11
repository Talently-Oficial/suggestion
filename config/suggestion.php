<?php

return [
    'environment' => env('SUGGESTION_ENVIRONMENT', ''),
    'url' => [
        'qa' => env('SUGGESTION_URL', ''),
        'production' => env('SUGGESTION_URL', ''),
    ],
    'api_key' => env('SUGGESTION_API_KEY', ''),
];