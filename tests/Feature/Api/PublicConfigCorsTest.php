<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('public config CORS', function () {
    it('sends the CORS header on the v2.1 config endpoint', function () {
        $this->getJson('/api/v2.1/config', ['Origin' => 'https://help.pixelfed.org'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['version', 'signup', 'features', 'limits']);
    });

    it('sends the CORS header on the v2 instance endpoint', function () {
        $this->getJson('/api/v2/instance', ['Origin' => 'https://help.pixelfed.org'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['domain', 'title', 'registrations', 'contact', 'rules']);
    });
});
