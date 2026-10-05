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

    /*
    | Offered as one-tap choices when a household has no activities yet.
    */
    'templates' => [
        ['name' => 'Ganti sprei', 'icon' => '🛏️'],
        ['name' => 'Isi galon', 'icon' => '💧'],
        ['name' => 'Ganti gas LPG', 'icon' => '🔥'],
        ['name' => 'Kuras bak mandi', 'icon' => '🛁'],
        ['name' => 'Bayar listrik', 'icon' => '⚡'],
        ['name' => 'Cuci motor', 'icon' => '🏍️'],
        ['name' => 'Ganti oli motor', 'icon' => '🛢️'],
        ['name' => 'Bersihkan kulkas', 'icon' => '🧊'],
        ['name' => 'Servis AC', 'icon' => '❄️'],
        ['name' => 'Potong kuku anak', 'icon' => '✂️'],
        ['name' => 'Ganti sikat gigi', 'icon' => '🪥'],
        ['name' => 'Beli beras', 'icon' => '🍚'],
    ],

    /*
    | Icon picked automatically when a new activity's name contains the
    | keyword. The first match wins.
    */
    'icon_keywords' => [
        'sprei' => '🛏️', 'kasur' => '🛏️', 'bantal' => '🛏️',
        'galon' => '💧', 'air' => '💧',
        'gas' => '🔥', 'lpg' => '🔥',
        'bak' => '🛁', 'kamar' => '🛁',
        'listrik' => '⚡', 'token' => '⚡',
        'oli' => '🛢️',
        'motor' => '🏍️',
        'mobil' => '🚗', 'bensin' => '⛽',
        'ac' => '❄️',
        'kulkas' => '🧊',
        'kuku' => '✂️', 'rambut' => '💈',
        'sikat' => '🪥', 'gigi' => '🦷',
        'beras' => '🍚', 'belanja' => '🛒',
        'cuci' => '🧺', 'laundry' => '🧺', 'baju' => '🧺',
        'kucing' => '🐈', 'anjing' => '🐕', 'ikan' => '🐟',
        'tanaman' => '🪴', 'siram' => '🪴',
        'obat' => '💊', 'vitamin' => '💊',
        'bayar' => '💳', 'kos' => '🏠', 'sampah' => '🗑️',
    ],

    'default_icon' => '📌',

    /*
    | Reminders are only pushed between these hours (24h, in this timezone)
    | so nobody gets woken up at night.
    */
    'reminder_timezone' => 'Asia/Jakarta',
    'reminder_hours' => [7, 20],

];
