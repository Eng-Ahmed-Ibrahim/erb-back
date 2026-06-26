<?php

/**
 * Default configuration for the application.
 * image path
 *
 * @var array
 *
 * @return array
 */
return [
    'user_image_path' => 'storage/default/user/user.jpg',
    'membership_cards' => [
        'replacement_card_fee' => env('MEMBERSHIP_CARD_REPLACEMENT_FEE', 50.00), // رسوم إصدار بطاقة بديلة
    ],
];
