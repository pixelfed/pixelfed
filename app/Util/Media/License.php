<?php

namespace App\Util\Media;

class License {

    public static function get()
    {
        return [
            1 => [
                "id" => 1,
                "title" => "All Rights Reserved",
                "name" => "All Rights Reserved",
                "description" => "You, the copyright holder, reserve all rights provided by copyright law, such as the right to make copies, distribute your work, perform your work, license, or otherwise exploit your work; no rights are waived under this license.",
                "terms" => [],
                "url" => url('/site/kb/licenses')
            ],
            5 => [
                "id" => 5,
                "title" => "Public Domain",
                "name" => "Public Domain Work",
                "description" => "Works, or aspects of copyrighted works, which copyright law does not protect.",
                "terms" => [],
                "url" => url('/site/kb/licenses')
            ],
            6 => [
                "id" => 6,
                "title" => "Public Domain (CC0)",
                "name" => "Public Domain Dedication (CC0)",
                "description" => "You, the copyright holder, waive your interest in your work and place the work as completely as possible in the public domain so others may freely exploit and use the work without restriction under copyright or database law.",
                "terms" => [],
                "url" => url('/site/kb/licenses')
            ],
            11 => [
                "id" => 11,
                "title" => "CC BY",
                "name" => "Attribution",
                "description" => "This license allows reusers to distribute, remix, adapt, and build upon the material in any medium or format, so long as attribution is given to the creator. The license allows for commercial use.",
                "terms" => [
                    "Credit must be given to the creator",
                ],
                "url" => "https://creativecommons.org/licenses/by/4.0/"
            ],
            12 => [
                "id" => 12,
                "title" => "CC BY-SA",
                "name" => "Attribution-ShareAlike",
                "description" => "This license allows reusers to distribute, remix, adapt, and build upon the material in any medium or format, so long as attribution is given to the creator. The license allows for commercial use. If you remix, adapt, or build upon the material, you must license the modified material under identical terms.",
                "terms" => [
                    "Credit must be given to the creator",
                    "Adaptations must be shared under the same terms"
                ],
                "url" => "https://creativecommons.org/licenses/by-sa/4.0/"
            ],
            13 => [
                "id" => 13,
                "title" => "CC BY-NC",
                "name" => "Attribution-NonCommercial",
                "description" => "This license allows reusers to distribute, remix, adapt, and build upon the material in any medium or format for noncommercial purposes only, and only so long as attribution is given to the creator.",
                "terms" => [
                    "Credit must be given to the creator",
                    "Only noncommercial uses of the work are permitted"
                ],
                "url" => "https://creativecommons.org/licenses/by-nc/4.0/"
            ],
            14 => [
                "id" => 14,
                "title" => "CC BY-NC-SA",
                "name" => "Attribution-NonCommercial-ShareAlike",
                "description" => "This license allows reusers to distribute, remix, adapt, and build upon the material in any medium or format for noncommercial purposes only, and only so long as attribution is given to the creator. If you remix, adapt, or build upon the material, you must license the modified material under identical terms.",
                "terms" => [
                    "Credit must be given to the creator",
                    "Only noncommercial uses of the work are permitted",
                    "Adaptations must be shared under the same terms"
                ],
                "url" => "https://creativecommons.org/licenses/by-nc-sa/4.0/"
            ],
            15 => [
                "id" => 15,
                "title" => "CC BY-ND",
                "name" => "Attribution-NoDerivs",
                "description" => "This license allows reusers to copy and distribute the material in any medium or format in unadapted form only, and only so long as attribution is given to the creator. The license allows for commercial use.",
                "terms" => [
                    "Credit must be given to the creator",
                    "No derivatives or adaptations of the work are permitted"
                ],
                "url" => "https://creativecommons.org/licenses/by-nd/4.0/"
            ],
            16 => [
                "id" => 16,
                "title" => "CC BY-NC-ND",
                "name" => "Attribution-NonCommercial-NoDerivs",
                "description" => "This license allows reusers to copy and distribute the material in any medium or format in unadapted form only, for noncommercial purposes only, and only so long as attribution is given to the creator.",
                "terms" => [
                    "Credit must be given to the creator",
                    "Only noncommercial uses of the work are permitted",
                    "No derivatives or adaptations of the work are permitted"
                ],
                "url" => "https://creativecommons.org/licenses/by-nc-nd/4.0/"
            ]
        ];
    }

    public static function keys()
    {
        return array_keys(self::get());
    }

//     public static function getId($index)
//     {
//         return self::get()[$index]['id'];
//     }

//     public static function names()
//     {
//         return collect(self::get())
//             ->map(function($v) {
//                 return $v['title'];
//             })
//             ->values()
//             ->toArray();
//     }

    public static function nameToId($name)
    {
    	$license = collect(self::get())
    		->filter(function($l) use($name) {
    			return $l['title'] == $name;
    		})
    		->first();

    	if(!$license || $license['id'] < 2) {
    		return null;
    	}

    	return $license['id'];
    }
}
