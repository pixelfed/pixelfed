<?php

/*
 *   Experimental configuration options
 *
 *   (Use at your own risk)
 */

return [
	// Hidden like counts (deprecated)
	'lc' => env('EXP_LC', false),

	// Recommendations (deprecated)
	'rec' => false,

	// Loops feature (deprecated)
	'loops' => false,

	// Text only posts (alpha)
	'top' => env('EXP_TOP', false),

	// Poll statuses (alpha)
	'polls' => env('EXP_POLLS', false),

	// Cached public timeline for larger instances (beta)
	'cached_public_timeline' => env('EXP_CPT', false),

	'cached_home_timeline' => env('EXP_CHT', false),

	// Groups (unreleased)
	'gps' => env('EXP_GPS', false),

	// Single page application (beta)
	'spa' => true,

	// Enforce Mastoapi Compatibility (alpha)
	'emc' => env('EXP_EMC', true),

	// HLS Live Streaming
	'hls' => env('HLS_LIVE', false),

	// Post Update/Edits
	'pue' => env('EXP_PUE', true),

	'autolink' => env('EXP_AUTOLINK_V2', false),
];
