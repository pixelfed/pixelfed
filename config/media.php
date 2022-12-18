<?php

return [
	'delete_local_after_cloud' => env('MEDIA_DELETE_LOCAL_AFTER_CLOUD', true),

	'exif' => [
		'database' => env('MEDIA_EXIF_DATABASE', false),
	],
];
