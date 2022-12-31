<?php

namespace App\Util\Media;

class Filter {

	public static $filters = [
		'1984' => 'filter-1977',
		'Azen' => 'filter-aden',
		'Astairo' => 'filter-amaro',
		'Grassbee' => 'filter-ashby',
		'Bookrun' => 'filter-brannan',
		'Borough' => 'filter-brooklyn',
		'Farms' => 'filter-charmes',
		'Hairsadone' => 'filter-clarendon',
		'Cleana ' => 'filter-crema',
		'Catpatch' => 'filter-dogpatch',
		'Earlyworm' => 'filter-earlybird',
		'Plaid' => 'filter-gingham',
		'Kyo' => 'filter-ginza',
		'Yefe' => 'filter-hefe',
		'Goddess' => 'filter-helena',
		'Yards' => 'filter-hudson',
		'Quill' => 'filter-inkwell',
		'Rankine' => 'filter-kelvin',
		'Juno' => 'filter-juno',
		'Mark' => 'filter-lark',
		'Chill' => 'filter-lofi',
		'Van' => 'filter-ludwig',
		'Apache' => 'filter-maven',
		'May' => 'filter-mayfair',
		'Ceres' => 'filter-moon',
		'Knoxville' => 'filter-nashville',
		'Felicity' => 'filter-perpetua',
		'Sandblast' => 'filter-poprocket',
		'Daisy' => 'filter-reyes',
		'Elevate' => 'filter-rise',
		'Nevada' => 'filter-sierra',
		'Futura' => 'filter-skyline',
		'Sleepy' => 'filter-slumber',
		'Steward' => 'filter-stinson',
		'Savoy' => 'filter-sutro',
		'Blaze' => 'filter-toaster',
		'Apricot' => 'filter-valencia',
		'Gloming' => 'filter-vesper',
		'Walter' => 'filter-walden',
		'Poplar' => 'filter-willow',
		'Xenon' => 'filter-xpro-ii'
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
