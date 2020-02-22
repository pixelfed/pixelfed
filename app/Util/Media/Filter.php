<?php

namespace App\Util\Media;

class Filter
{
    public static $filters = [
        '1977' => 'filter-1977',
        'Aden' => 'filter-aden',
        'Amaro' => 'filter-amaro',
        'Ashby' => 'filter-ashby',
        'Brannan' => 'filter-brannan',
        'Brooklyn' => 'filter-brooklyn',
        'Charmes' => 'filter-charmes',
        'Clarendon' => 'filter-clarendon',
        'Crema' => 'filter-crema',
        'Dogpatch' => 'filter-dogpatch',
        'Earlybird' => 'filter-earlybird',
        'Gingham' => 'filter-gingham',
        'Ginza' => 'filter-ginza',
        'Hefe' => 'filter-hefe',
        'Helena' => 'filter-helena',
        'Hudson' => 'filter-hudson',
        'Inkwell' => 'filter-inkwell',
        'Kelvin' => 'filter-kelvin',
        'Kuno' => 'filter-juno',
        'Lark' => 'filter-lark',
        'Lo-Fi' => 'filter-lofi',
        'Ludwig' => 'filter-ludwig',
        'Maven' => 'filter-maven',
        'Mayfair' => 'filter-mayfair',
        'Moon' => 'filter-moon',
        'Nashville' => 'filter-nashville',
        'Perpetua' => 'filter-perpetua',
        'Poprocket' => 'filter-poprocket',
        'Reyes' => 'filter-reyes',
        'Rise' => 'filter-rise',
        'Sierra' => 'filter-sierra',
        'Skyline' => 'filter-skyline',
        'Slumber' => 'filter-slumber',
        'Stinson' => 'filter-stinson',
        'Sutro' => 'filter-sutro',
        'Toaster' => 'filter-toaster',
        'Valencia' => 'filter-valencia',
        'Vesper' => 'filter-vesper',
        'Walden' => 'filter-walden',
        'Willow' => 'filter-willow',
        'X-Pro II' => 'filter-xpro-ii'
    ];

    public static function classes()
    {
        return array_values(self::$filters);
    }

    public static function names()
    {
        return array_keys(self::$filters);
    }
}
