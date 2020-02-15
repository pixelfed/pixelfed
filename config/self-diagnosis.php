<?php

return [

    /*
     * A list of environment aliases mapped to the actual environment configuration.
     */
    'environment_aliases' => [
        'prod' => 'production',
        'live' => 'production',
        'local' => 'development',
    ],

    /*
     * Common checks that will be performed on all environments.
     */
    'checks' => [
        \BeyondCode\SelfDiagnosis\Checks\AppKeyIsSet::class,
        \BeyondCode\SelfDiagnosis\Checks\CorrectPhpVersionIsInstalled::class,
        \BeyondCode\SelfDiagnosis\Checks\DatabaseCanBeAccessed::class => [
            'default_connection' => true,
            'connections' => [],
        ],
        \BeyondCode\SelfDiagnosis\Checks\DirectoriesHaveCorrectPermissions::class => [
            'directories' => [
                storage_path(),
                base_path('bootstrap/cache'),
            ],
        ],
        \BeyondCode\SelfDiagnosis\Checks\EnvFileExists::class,
        \BeyondCode\SelfDiagnosis\Checks\MaintenanceModeNotEnabled::class,
        \BeyondCode\SelfDiagnosis\Checks\MigrationsAreUpToDate::class,
        \BeyondCode\SelfDiagnosis\Checks\PhpExtensionsAreInstalled::class => [
            'extensions' => [
                'openssl',
                'PDO',
                'mbstring',
                'tokenizer',
                'xml',
                'ctype',
                'json',
                'redis',
                'bcmath',
                'curl',
                'exif',
                'iconv',
                'intl',
                'zip'
            ],
            'include_composer_extensions' => true,
        ],
        \BeyondCode\SelfDiagnosis\Checks\RedisCanBeAccessed::class => [
           'default_connection' => false,
           'connections' => [],
        ],
        \BeyondCode\SelfDiagnosis\Checks\StorageDirectoryIsLinked::class,
    ],

    /*
     * Environment specific checks that will only be performed for the corresponding environment.
     */
    'environment_checks' => [
        'development' => [
            \BeyondCode\SelfDiagnosis\Checks\ComposerWithDevDependenciesIsUpToDate::class,
            \BeyondCode\SelfDiagnosis\Checks\ConfigurationIsNotCached::class,
            \BeyondCode\SelfDiagnosis\Checks\RoutesAreNotCached::class,
            \BeyondCode\SelfDiagnosis\Checks\ExampleEnvironmentVariablesAreUpToDate::class,
        ],
        'production' => [
            \BeyondCode\SelfDiagnosis\Checks\ComposerWithoutDevDependenciesIsUpToDate::class,
            \BeyondCode\SelfDiagnosis\Checks\ConfigurationIsCached::class,
            \BeyondCode\SelfDiagnosis\Checks\DebugModeIsNotEnabled::class,
            \BeyondCode\SelfDiagnosis\Checks\PhpExtensionsAreDisabled::class => [
                'extensions' => [
                    'xdebug',
                ],
            ],
            \BeyondCode\SelfDiagnosis\Checks\RoutesAreCached::class,
            //\BeyondCode\SelfDiagnosis\Checks\ServersArePingable::class => [
            //    'servers' => [
            //        'www.google.com',
            //        ['host' => 'www.google.com', 'port' => 8080],
            //        '8.8.8.8',
            //        ['host' => '8.8.8.8', 'port' => 8080, 'timeout' => 5],
            //    ],
            //],
            //\BeyondCode\SelfDiagnosis\Checks\SupervisorProgramsAreRunning::class => [
            //    'programs' => [
            //        'horizon',
            //    ],
            //    'restarted_within' => 300,
            //],
            //\BeyondCode\SelfDiagnosis\Checks\HorizonIsRunning::class,
        ],
    ],

];
