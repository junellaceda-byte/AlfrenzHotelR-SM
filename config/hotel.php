<?php

/*
|--------------------------------------------------------------------------
| Alfrenzo's Hotel: pricing and stay rules
|--------------------------------------------------------------------------
| Default values used by the receptionist side. When the admin side stores
| these in the database, this file stays as the fallback.
*/
return [
    'check_in_time'      => '14:00',
    'check_out_time'     => '12:00',
    'short_stay_hours'   => 3,
    'tax_rate'           => 0,
    'short_stay_options' => [3, 6, 12],

    'prices' => [
        'short_stay' => [3 => 600, 6 => 1000, 12 => 1400],
        'overnight'  => ['no_breakfast' => 1800, 'with_breakfast' => 2200],
        'extra_bed'  => ['no_breakfast' => 500, 'with_breakfast' => 700],
        'extra_hour' => 150,
    ],

    'discounts' => ['senior' => 0.20, 'pwd' => 0.20],
];