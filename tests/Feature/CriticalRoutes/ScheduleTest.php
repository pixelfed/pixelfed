<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Schedule Registration Tests
|--------------------------------------------------------------------------
|
| These tests verify that all expected scheduled commands are registered
| and configured correctly. They catch regressions when refactoring
| bootstrap/app.php or moving schedule definitions.
|
*/

function getScheduledEvents(): Collection
{
    // Force the schedule to be resolved through the console kernel
    // which triggers withSchedule() in bootstrap/app.php
    Artisan::call('schedule:list');

    return collect(app(Schedule::class)->events());
}

test('schedule registers expected commands', function () {
    $events = getScheduledEvents();
    $commands = $events->map(fn ($e) => $e->command ?? '')->implode(' ');

    expect($commands)->toContain('media:optimize')
        ->toContain('media:gc')
        ->toContain('horizon:snapshot')
        ->toContain('story:gc')
        ->toContain('gc:failedjobs')
        ->toContain('gc:passwordreset')
        ->toContain('gc:sessions')
        ->toContain('app:weekly-instance-scan');
});

test('all scheduled commands run on one server', function () {
    $events = getScheduledEvents();

    expect($events)->not->toBeEmpty();

    $events->each(function ($event) {
        expect($event->onOneServer)->toBeTrue(
            "Command '{$event->command}' should run on one server"
        );
    });
});
