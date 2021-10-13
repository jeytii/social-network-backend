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
];
