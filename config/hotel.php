<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trading country
    |--------------------------------------------------------------------------
    |
    | The country the hotel itself is in. Used as the hint when a phone number is
    | written without a country code, so reception can type 0999 123 456 the way
    | they would say it and still have it stored as something dialable.
    |
    | Numbers from anywhere else are accepted too, in international format, which
    | is what most of the hotel's guests will be typing.
    |
    */

    'country' => env('HOTEL_COUNTRY', 'MW'),

];
