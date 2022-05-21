<?php

return [
    'ffmpeg' => [
        'binaries' => env('FFMPEG_BINARIES', 'ffmpeg'),

        'threads' => 12,   // set to false to disable the default 'threads' filter
    ],

    'ffprobe' => [
        'binaries' => env('FFPROBE_BINARIES', 'ffprobe'),
    ],

    'timeout' => 3600,

    'enable_logging' => env('FFMPEG_LOG', false),

    'set_command_and_error_output_on_exception' => false,

    'temporary_files_root' => env('FFMPEG_TEMPORARY_FILES_ROOT', sys_get_temp_dir()),
];
