<?php

return [
    'formats' => [
        'username' => '/^[a-zA-Z0-9_]+$/',
        'phone_number' => '/^(0|63)?9[0-9]{9}$/',
    ],
    
    'max_lengths' => [
        'long_text' => 180,
        'bio' => 120,
    ],

    'notifications' => [
        'user_followed' => 1,
        'post_liked' => 2,
        'commented_on_post' => 3,
        'mentioned_on_comment' => 4,
    ],

    'response' => [
        'user' => [
            'basic' => ['name', 'username', 'gender', 'image_url']
        ]
    ]
];
