<?php

use App\Support\MemoryLimit;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->originalMemoryLimit = ini_get('memory_limit');
    ini_set('memory_limit', '512M');
});

afterEach(function () {
    ini_set('memory_limit', $this->originalMemoryLimit);
});

it('parses php shorthand quantities', function () {
    expect(MemoryLimit::toBytes('1024M'))->toBe(1073741824)
        ->and(MemoryLimit::toBytes('1G'))->toBe(1073741824)
        ->and(MemoryLimit::toBytes('512k'))->toBe(524288)
        ->and(MemoryLimit::toBytes(' 256M '))->toBe(268435456)
        ->and(MemoryLimit::toBytes(268435456))->toBe(268435456)
        ->and(MemoryLimit::toBytes('-1'))->toBe(-1)
        ->and(MemoryLimit::toBytes(-1))->toBe(-1);
});

it('treats empty and invalid quantities as unknown', function () {
    expect(MemoryLimit::toBytes(''))->toBeNull()
        ->and(MemoryLimit::toBytes(null))->toBeNull()
        ->and(MemoryLimit::toBytes(false))->toBeNull()
        ->and(MemoryLimit::toBytes('garbage'))->toBeNull();
});

it('raises the memory limit when the requested value is higher', function () {
    expect(MemoryLimit::raise('1024M'))->toBeTrue()
        ->and(MemoryLimit::toBytes(ini_get('memory_limit')))->toBe(1073741824);
});

it('never lowers the memory limit', function () {
    expect(MemoryLimit::raise('128M'))->toBeFalse()
        ->and(ini_get('memory_limit'))->toBe('512M');
});

it('leaves an unlimited memory limit alone', function () {
    ini_set('memory_limit', '-1');

    expect(MemoryLimit::raise('1024M'))->toBeFalse()
        ->and(ini_get('memory_limit'))->toBe('-1');
});

it('ignores invalid requested values', function () {
    expect(MemoryLimit::raise('garbage'))->toBeFalse()
        ->and(MemoryLimit::raise(null))->toBeFalse()
        ->and(ini_get('memory_limit'))->toBe('512M');
});

it('clamps to max_memory_limit without emitting a warning', function () {
    $script = <<<'PHP'
set_error_handler(function ($level, $message) {
    throw new ErrorException($message, 0, $level);
});
require $argv[1];
App\Support\MemoryLimit::raise('1024M');
echo ini_get('memory_limit');
PHP;

    $process = new Process([
        PHP_BINARY,
        '-n',
        '-d', 'max_memory_limit=512M',
        '-d', 'memory_limit=128M',
        '-r', $script,
        dirname(__DIR__, 2).'/app/Support/MemoryLimit.php',
    ]);

    $process->run();

    expect($process->getErrorOutput())->toBe('')
        ->and($process->isSuccessful())->toBeTrue()
        ->and(MemoryLimit::toBytes(trim($process->getOutput())))->toBe(536870912);
})->skip(PHP_VERSION_ID < 80500, 'max_memory_limit requires PHP 8.5');
