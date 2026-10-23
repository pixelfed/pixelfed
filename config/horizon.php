<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => 'horizon',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon-'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Notification Routing
    |--------------------------------------------------------------------------
    |
    | Horizon can notify you when a queue's LongWaitDetected threshold (see
    | the `waits` option below) is exceeded. These are read by
    | HorizonServiceProvider::boot() and are all optional; leave them unset
    | to disable notification delivery entirely.
    |
    */

    'notification_routing' => [
        'mail_to' => env('HORIZON_NOTIFICATIONS_MAIL'),
        'slack_webhook_url' => env('HORIZON_NOTIFICATIONS_SLACK_WEBHOOK'),
        'slack_channel' => env('HORIZON_NOTIFICATIONS_SLACK_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:feed' => 30,
        'redis:follow' => 30,
        'redis:shared' => 30,
        'redis:default' => 30,
        'redis:inbox' => 30,
        'redis:low' => 30,
        'redis:high' => 30,
        'redis:delete' => 30,
        'redis:story' => 30,
        'redis:mmo' => 30,
        'redis:intbg' => 30,
        'redis:adelete' => 30,
        'redis:groups' => 30,
        'redis:move' => 30,
        'redis:pushnotify' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon worker
    | may consume before it is terminated and restarted. You should set
    | this value according to the resources available to your server.
    |
    */

    'memory_limit' => env('HORIZON_MEMORY_LIMIT', 64),

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    | Queues are split across supervisors by workload rather than lumped into
    | one auto-balanced pool, because Horizon's `balance: auto` does not
    | honor queue array order for priority - a burst on one queue can starve
    | another sharing the same supervisor regardless of their names:
    |
    | - supervisor-priority: user-facing federation delivery (follows,
    |   deletes, DMs), inbox processing, and push notifications. These
    |   should never wait behind a burst of media processing or feed fanout.
    | - supervisor-fanout: timeline/story/group fanout writes triggered by
    |   posts, likes, and follows. Bursty (one post can fan out to many
    |   followers) but not CPU-heavy.
    | - supervisor-media: image/video optimization, resizing, and thumbnail
    |   generation (the `mmo` queue). CPU/IO heavy, so it runs a fixed
    |   worker pool (`balance: false`) instead of auto-scaling, which would
    |   otherwise let it claim workers away from the other pools under load.
    | - supervisor-background: imports, instance crawling, account
    |   deletion/migration, and other maintenance work that isn't
    |   user-facing time-sensitive.
    |
    */

    'defaults' => [
        'supervisor-priority' => [
            'connection' => 'redis',
            'queue' => ['high', 'inbox', 'pushnotify', 'follow', 'default', 'shared'],
            'balance' => env('HORIZON_BALANCE_STRATEGY', 'auto'),
            'autoScalingStrategy' => 'time',
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'minProcesses' => env('HORIZON_MIN_PROCESSES', 1),
            'maxProcesses' => env('HORIZON_MAX_PROCESSES', 10),
            'memory' => env('HORIZON_SUPERVISOR_MEMORY', 64),
            'tries' => env('HORIZON_SUPERVISOR_TRIES', 3),
            'nice' => env('HORIZON_SUPERVISOR_NICE', 0),
            'timeout' => env('HORIZON_SUPERVISOR_TIMEOUT', 300),
        ],

        'supervisor-fanout' => [
            'connection' => 'redis',
            'queue' => ['feed', 'story', 'groups'],
            'balance' => env('HORIZON_BALANCE_STRATEGY', 'auto'),
            'autoScalingStrategy' => 'time',
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'minProcesses' => env('HORIZON_MIN_PROCESSES', 1),
            'maxProcesses' => env('HORIZON_FANOUT_MAX_PROCESSES', 6),
            'memory' => env('HORIZON_SUPERVISOR_MEMORY', 64),
            'tries' => env('HORIZON_SUPERVISOR_TRIES', 3),
            'nice' => env('HORIZON_SUPERVISOR_NICE', 0),
            'timeout' => env('HORIZON_SUPERVISOR_TIMEOUT', 300),
        ],

        'supervisor-media' => [
            'connection' => 'redis',
            'queue' => ['mmo'],
            'balance' => false,
            'minProcesses' => env('HORIZON_MIN_PROCESSES', 1),
            'maxProcesses' => env('HORIZON_MEDIA_MAX_PROCESSES', 4),
            'memory' => env('HORIZON_SUPERVISOR_MEMORY', 64),
            'tries' => env('HORIZON_SUPERVISOR_TRIES', 3),
            'nice' => env('HORIZON_SUPERVISOR_NICE', 0),
            'timeout' => env('HORIZON_SUPERVISOR_TIMEOUT', 300),
        ],

        'supervisor-background' => [
            'connection' => 'redis',
            'queue' => ['low', 'delete', 'adelete', 'move', 'intbg'],
            'balance' => env('HORIZON_BALANCE_STRATEGY', 'auto'),
            'autoScalingStrategy' => 'time',
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'minProcesses' => env('HORIZON_MIN_PROCESSES', 1),
            'maxProcesses' => env('HORIZON_BACKGROUND_MAX_PROCESSES', 4),
            'memory' => env('HORIZON_SUPERVISOR_MEMORY', 64),
            'tries' => env('HORIZON_SUPERVISOR_TRIES', 3),
            'nice' => env('HORIZON_SUPERVISOR_NICE', 0),
            'timeout' => env('HORIZON_SUPERVISOR_TIMEOUT', 300),
        ],
    ],

    'environments' => [
        'production' => [
            // All values come from `defaults` above (env-configurable)
        ],

        'local' => [
            'supervisor-priority' => ['maxProcesses' => 4],
            'supervisor-fanout' => ['maxProcesses' => 2],
            'supervisor-media' => ['maxProcesses' => 2],
            'supervisor-background' => ['maxProcesses' => 2],
        ],
    ],

    'darkmode' => env('HORIZON_DARKMODE', false),
];
