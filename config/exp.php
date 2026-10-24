<?php

/*
 *   Experimental configuration options
 *
 *   (Use at your own risk)
 */

return [
    // Text only posts (alpha)
    'top' => env('EXP_TOP', false),

    // Poll statuses (alpha)
    'polls' => env('EXP_POLLS', false),

    // Groups (unreleased)
    'gps' => env('EXP_GPS', false),

    // Single page application (beta)
    'spa' => true,

    // Enforce Mastoapi Compatibility (alpha)
    'emc' => env('EXP_EMC', true),

    // HLS Live Streaming
    'hls' => env('HLS_LIVE', false),

    'autolink' => env('EXP_AUTOLINK_V2', false),
];
