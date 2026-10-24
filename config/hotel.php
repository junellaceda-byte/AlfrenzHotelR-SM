<?php

/*
|--------------------------------------------------------------------------
| Alfrenzo's Hotel: pricing and stay rules
|--------------------------------------------------------------------------
| Default values used by the receptionist side. When the admin side stores
| these in the database, this file stays as the fallback.
*/

return [
    // Times used by the older reservation and check-in forms
    'check_in_time'    => '14:00',
    'check_out_time'   => '12:00',
    'short_stay_hours' => 3,

    // The hotel is non-VAT, so no tax is added
    'tax_rate' => 0,

    // Short stay packages (hours)
    'short_stay_options' => [3, 6, 12],

    // All prices are in pesos
    'prices' => [
        // Short stay: hours => price per room
        'short_stay' => [3 => 600, 6 => 1000, 12 => 1400],
        // 24-hour stay: price per room
        'overnight'  => ['no_breakfast' => 1800, 'with_breakfast' => 2200],
        // Extra bed: price per bed, depends on the breakfast choice
        'extra_bed'  => ['no_breakfast' => 500, 'with_breakfast' => 700],
    ],

    // Senior Citizen and PWD discount, applied to the whole subtotal
    'discounts' => ['senior' => 0.20, 'pwd' => 0.20],
];