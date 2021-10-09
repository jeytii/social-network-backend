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
        'username' => 6,
    ],

    'image' => [
        'min_res' => 100,
        'max_res' => 800,
    ],

    'notifications' => [
        'user_followed' => 1,
        'post_liked' => 2,
        'comment_liked' => 3,
        'commented_on_post' => 4,
        'mentioned_on_comment' => 5,
    ],

    'response' => [
        'user' => [
            'basic' => ['name', 'username', 'gender', 'image_url']
        ]
    ]
];
