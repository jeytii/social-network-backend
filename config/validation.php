<?php

return [
    'formats' => [
        'username' => '/^[a-zA-Z0-9_]+$/',
    ],
    
    'max_lengths' => [
        'username' => 30,
        'long_text' => 180,
        'bio' => 120,
    ],

    'min_lengths' => [
        'name' => 2,
        'username' => 6,
        'password' => 8,
    ],

    'image' => [
        'min_res' => 100,
        'max_res' => 800,
    ],

    'expiration' => [
        'verification' => 10,
        'password_reset' => 30,
    ],

    'attempts' => [
        'change_username' => [
            'max' => 3,
            'interval' => 72, // in hours
        ],
        'change_email_address' => [
            'max' => 3,
            'interval' => 72, // in hours
        ],
        'change_password' => [
            'max' => 3,
            'interval' => 24, // in hours
        ],
    ]
];
