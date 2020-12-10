<?php

namespace App\Util\Media;

use App\Util\Blurhash\Blurhash as BlurhashEngine;
use App\Media;

class Blurhash {

	public static function generate(Media $media)
	{
		if(!in_array($media->mime, ['image/png', 'image/jpeg'])) {
			return;
		}

		$file  = storage_path('app/' . $media->thumbnail_path);

		if(!is_file($file)) {
			return;
		}

		$image = imagecreatefromstring(file_get_contents($file));
		$width = imagesx($image);
		$height = imagesy($image);

		$pixels = [];
		for ($y = 0; $y < $height; ++$y) {
			$row = [];
			for ($x = 0; $x < $width; ++$x) {
				$index = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $index);

				$row[] = [$colors['red'], $colors['green'], $colors['blue']];
			}
			$pixels[] = $row;
		}

		$components_x = 4;
		$components_y = 4;
		$blurhash = BlurhashEngine::encode($pixels, $components_x, $components_y);
		if(strlen($blurhash) > 191) {
			return;
		}
		return $blurhash;
	}

}