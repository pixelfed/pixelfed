<?php

namespace App\Util\Media;

use App\Util\Blurhash\Blurhash as BlurhashEngine;
use App\Media;

class Blurhash {

	const DEFAULT_HASH = 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay';

	public static function generate(Media $media)
	{
		if(!in_array($media->mime, ['image/png', 'image/jpeg', 'video/mp4'])) {
			return self::DEFAULT_HASH;
		}

		if($media->thumbnail_path == null) {
			return self::DEFAULT_HASH;
		}

		$file  = storage_path('app/' . $media->thumbnail_path);

		if(!is_file($file)) {
			return self::DEFAULT_HASH;
		}

		$image = imagecreatefromstring(file_get_contents($file));
		if(!$image) {
			return self::DEFAULT_HASH;
		}
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

		// Free the allocated GdImage object from memory:
		imagedestroy($image);

		$components_x = 4;
		$components_y = 4;
		$blurhash = BlurhashEngine::encode($pixels, $components_x, $components_y);
		if(strlen($blurhash) > 191) {
			return self::DEFAULT_HASH;
		}
		return $blurhash;
	}

}
