<?php

return [

    /*
    | Bump this when the terms text changes; users are asked to accept again.
    */
    'terms_version' => '2026-10-05',

    /*
    | One-click login without Google, for local development only.
    | Ignored unless APP_ENV=local.
    */
    'dev_login' => (bool) env('CATATAN_DEV_LOGIN', false),

];
