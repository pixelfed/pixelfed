<?php

namespace App\Util\Media;

class Filter {

	public static $filters = [
		'Luke' => 'filter-luke', 
		'Minotaur' => 'filter-minotaur', 
		'Magma' => 'filter-magma', 
		'Spell' => 'filter-spell', 
		'Dimension' => 'filter-dimension', 
		'Fixie' => 'filter-fixie', 
		'Berlock' => 'filter-berlock', 
		'Bells' => 'filter-bells', 
		'Welcome' => 'filter-welcome', 
		'Abner' => 'filter-abner', 
		'Woodstock' => 'filter-woodstock', 
		'Fields' => 'filter-fields', 
		'Dinner' => 'filter-dinner', 
		'Boss' => 'filter-boss', 
		'Handbasket' => 'filter-handbasket', 
		'River' => 'filter-river', 
		'Quill' => 'filter-quill', 
		'Frosty' => 'filter-frosty', 
		'Hera' => 'filter-hera', 
		'Birdsong' => 'filter-birdsong', 
		'Canada' => 'filter-canada', 
		'Wendy' => 'filter-wendy', 
		'Shamus' => 'filter-shamus', 
		'Marylebone' => 'filter-marylebone', 
		'Luna' => 'filter-luna', 
		'Chattanooga' => 'filter-chattanooga', 
		'Felicity' => 'filter-felicity', 
		'Jazz' => 'filter-jazz', 
		'Doggett' => 'filter-doggett', 
		'Ascend' => 'filter-ascend', 
		'Tango' => 'filter-tango', 
		'Cherry' => 'filter-cherry', 
		'Nemo' => 'filter-nemo', 
		'Suitable' => 'filter-suitable', 
		'Mayor' => 'filter-mayor', 
		'Bread' => 'filter-bread', 
		'Iberia' => 'filter-iberia', 
		'Folded' => 'filter-folded', 
		'Cabin' => 'filter-cabin', 
		'Crayon' => 'filter-crayon', 
		'Expert' => 'filter-expert'
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