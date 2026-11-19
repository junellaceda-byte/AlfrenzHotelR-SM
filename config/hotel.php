<?php

/*
|--------------------------------------------------------------------------
| Alfrenzo's Hotel: pricing and stay rules
|--------------------------------------------------------------------------
| Default values used by the receptionist side. When the admin side stores
| these in the database, this file stays as the fallback.
*/
return [
    'check_in_time' => '14:00',
    'check_out_time' => '12:00',
    'short_stay_hours' => 3,
    'tax_rate' => 0,
    'short_stay_options' => [3, 6, 12],

    'prices' => [
        'short_stay' => [3 => 600, 6 => 1000, 12 => 1400],
        'overnight' => ['no_breakfast' => 1800, 'with_breakfast' => 2200],
        'extra_bed' => ['no_breakfast' => 500, 'with_breakfast' => 700],
        'extra_hour' => 150,
    ],

    'discounts' => ['senior' => 0.20, 'pwd' => 0.20],
    // TEMPORARY amenities for the Add button. The admin side's amenities table replaces this list later.
    // The first three are from the client. The last three are SAMPLES: change or delete them.
    'amenities' => [
        'extra_bed_no_breakfast' => ['label' => 'Extra Bed (without breakfast)', 'price' => 500, 'unit' => 'each'],
        'extra_bed_with_breakfast' => ['label' => 'Extra Bed (with breakfast)',    'price' => 700, 'unit' => 'each'],
        'extra_hour' => ['label' => 'Extra Hour',                    'price' => 150, 'unit' => 'per hour'],
        'towel_pillow' => ['label' => 'Towel and Pillow Set (sample)', 'price' => 100, 'unit' => 'each'],
        'toiletries' => ['label' => 'Toiletries Kit (sample)',       'price' => 80,  'unit' => 'each'],
        'bottled_water' => ['label' => 'Bottled Water (sample)',        'price' => 30,  'unit' => 'each'],
    ],
];
