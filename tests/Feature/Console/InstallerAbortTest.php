<?php

use App\Console\Commands\Install\Installer;

/*
|--------------------------------------------------------------------------
| install
|--------------------------------------------------------------------------
|
| Declining at the missing-PHP-extension or missing-FFmpeg prompt must halt
| the installer before any environment/key/migration work happens. The
| check methods abort with exit;, so the guarantee we can assert here is the
| ordering: both checks run ahead of envProd() (APP_KEY generation) and
| dbMigrations() (migrate --force) inside installerSteps().
|
*/

function installerStepOrder(): array
{
    $method = new ReflectionMethod(Installer::class, 'installerSteps');
    $file = file($method->getFileName());
    $body = implode('', array_slice(
        $file,
        $method->getStartLine() - 1,
        $method->getEndLine() - $method->getStartLine() + 1
    ));

    preg_match_all('/\$this->(\w+)\(\);/', $body, $matches);

    return $matches[1];
}

it('runs the dependency checks before env, key generation and migrations', function () {
    $order = installerStepOrder();

    $php = array_search('checkPHPRequiredDependencies', $order, true);
    $ffmpeg = array_search('checkFFmpegDependencies', $order, true);
    $envProd = array_search('envProd', $order, true);
    $migrations = array_search('dbMigrations', $order, true);

    expect($php)->not->toBeFalse();
    expect($ffmpeg)->not->toBeFalse();
    expect($envProd)->not->toBeFalse();
    expect($migrations)->not->toBeFalse();

    expect($php)->toBeLessThan($envProd);
    expect($ffmpeg)->toBeLessThan($envProd);
    expect($php)->toBeLessThan($migrations);
    expect($ffmpeg)->toBeLessThan($migrations);
});

it('aborts both dependency checks instead of returning when the user declines', function () {
    foreach (['checkPHPRequiredDependencies', 'checkFFmpegDependencies'] as $name) {
        $method = new ReflectionMethod(Installer::class, $name);
        $file = file($method->getFileName());
        $body = implode('', array_slice(
            $file,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        expect($body)->toContain('exit;');
        expect($body)->not->toContain('return 1;');
    }
});

it('cannot be driven end to end through artisan because the abort uses exit;', function () {
    //
})->skip('install aborts with exit; and the extension/ffmpeg prompts only fire on real missing deps; it cannot be driven through artisan() without faking PHP internals');
