<?php

return [
    'formats' => [
        'username' => '/^[a-zA-Z0-9_]+$/',
        'phone_number' => '/^(0|63)?9[0-9]{9}$/',
    ],
    
    'max_lengths' => [
        'username' => 30,
        'long_text' => 180,
        'bio' => 120,
    ],

    'min_lengths' => [
        'name' => 2,
        'username' => 6,
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
            'interval' => 72,
        ],
        'change_email_address' => [
            'max' => 3,
            'interval' => 72,
        ],
        'change_phone_number' => [
            'max' => 3,
            'interval' => 72,
        ],
        'change_password' => [
            'max' => 3,
            'interval' => 24,
        ],
    ]
];
