<?php

return [
    'subdomain'      => env('AMOCRM_SUBDOMAIN'),
    'client_id'      => env('AMOCRM_CLIENT_ID'),
    'client_secret'  => env('AMOCRM_CLIENT_SECRET'),
    'redirect_uri'   => env('AMOCRM_REDIRECT_URI'),
    'token_file'     => storage_path('app/tokens.json'),
    'log_file'       => storage_path('logs/api.log'),
];
