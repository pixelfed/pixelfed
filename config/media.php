<?php

return [
	'exif' => [
		'database' => env('MEDIA_EXIF_DATABASE', false),
		'strip' => true
	],
	'types' => env('MEDIA_TYPES', 'image/jpeg,image/png,image/gif'),
	'photo' => [
		'optimize' => env('PF_OPTIMIZE_IMAGES', true),
		'quality' => (int) env('IMAGE_QUALITY', 80),
		'max_size' => env('MAX_PHOTO_SIZE', 15000),
		'max_album_length' => env('MAX_ALBUM_LENGTH', 4),
	],
];