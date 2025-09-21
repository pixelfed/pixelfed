<?php
namespace Deployer;

use Exception;

require 'recipe/laravel.php';

// Config
set('repository', 'git@github.com:kaptr-eu/pixelfed.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts
host('leonardo')
    ->set('deploy_path', '~/kaptr');

// Extra tasks
task('npm-install', function () {
    cd('{{release_path}}');
    run('npm install');
});

task('npm-build', function () {
    cd('{{release_path}}');
    # For some reason vite was not found after first install, hopefully this is an upstream error
    run('npm install && npm run production');
});

task('workers-stop', function () {
    cd('{{release_path}}');
    run('php artisan queue:restart');
});

task('ready-to-release', function () {
    $branch = runLocally('git rev-parse --abbrev-ref HEAD');
    if ($branch != "dev") {
        throw new Exception("not in 'dev' branch, current is: " . $branch);
    }

    $status = runLocally('git status');
    if (str_contains($status, 'is ahead')) {
        throw new Exception("you have local commits to push");
    }
});

// Hooks
before('deploy:info', 'ready-to-release');
after('deploy:failed', 'deploy:unlock');
after('deploy:vendors', 'npm-install');
after('npm-install', 'npm-build');
after('deploy:success', 'workers-stop');
