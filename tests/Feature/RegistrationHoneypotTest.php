<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('rejects registration as spam when the honeypot field is filled', function () {
    config(['cache.default' => 'array']);
    config(['honeypot.enabled' => true]);
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);
    config(['instance.enable_cc' => false]);

    $this->post('/register', [
        config('honeypot.name_field_name') => 'i-am-a-bot',
        'name' => 'Spam Bot',
        'username' => 'spambot',
        'email' => 'spambot@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agecheck' => 'on',
    ]);

    $this->assertDatabaseMissing('users', [
        'username' => 'spambot',
    ]);
});
