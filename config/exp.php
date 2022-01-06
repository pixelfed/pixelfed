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

	// Groups (unreleased)
	'gps' => env('EXP_GPS', false),

	// Single page application (beta)
	'spa' => true,

	// Enforce Mastoapi Compatibility (alpha)
	// Note: this may break 3rd party apps who use non-mastodon compliant fields
	'emc' => env('EXP_EMC', false),
];
