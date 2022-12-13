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
	*/

	'environments' => [
		'production' => [
			'supervisor-1' => [
				'connection'    => 'redis',
				'queue'         => ['high', 'default', 'follow', 'shared', 'inbox', 'feed', 'low', 'story', 'delete', 'mmo'],
				'balance'       => env('HORIZON_BALANCE_STRATEGY', 'auto'),
				'minProcesses'  => env('HORIZON_MIN_PROCESSES', 1),
				'maxProcesses'  => env('HORIZON_MAX_PROCESSES', 20),
				'memory'        => env('HORIZON_SUPERVISOR_MEMORY', 64),
				'tries'         => env('HORIZON_SUPERVISOR_TRIES', 3),
				'nice'          => env('HORIZON_SUPERVISOR_NICE', 0),
				'timeout'		=> env('HORIZON_SUPERVISOR_TIMEOUT', 300),
			],
		],

		'local' => [
			'supervisor-1' => [
				'connection'    => 'redis',
				'queue'         => ['high', 'default', 'follow', 'shared', 'inbox', 'feed', 'low', 'story', 'delete', 'mmo'],
				'balance'       => 'auto',
				'minProcesses' => 1,
				'maxProcesses'  => 20,
				'memory'        => 128,
				'tries'         => 3,
				'nice'          => 0,
				'timeout'       => 300
			],
		],
	],

	'darkmode' => env('HORIZON_DARKMODE', false),
];
