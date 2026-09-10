<?php

use App\Mail\CuratedRegisterNotifyAdmin;
use App\Mail\CuratedRegisterNotifyAdminUserResponse;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Curated register admin email escaping
|--------------------------------------------------------------------------
|
| User-provided reason_to_join / response message must be HTML-escaped in the
| admin notification emails (prevents injected phishing UI), while preserving
| line breaks.
|
*/

it('escapes user-provided content in the admin notification emails', function () {
    $verify = new CuratedRegister;
    $verify->username = 'attacker';
    $verify->email = 'attacker@example.com';
    $verify->reason_to_join = "<script>alert(1)</script>\nsecond line";
    $verify->save();

    $applicationHtml = (new CuratedRegisterNotifyAdmin($verify))->render();

    expect($applicationHtml)->not->toContain('<script>alert(1)</script>');
    expect($applicationHtml)->toContain('&lt;script&gt;');
    // Line breaks preserved via nl2br.
    expect($applicationHtml)->toContain('<br');

    $activity = new CuratedRegisterActivity;
    $activity->register_id = $verify->id;
    $activity->message = '<script>alert(2)</script>';
    $activity->save();

    $responseHtml = (new CuratedRegisterNotifyAdminUserResponse($activity))->render();

    expect($responseHtml)->not->toContain('<script>alert(2)</script>');
    expect($responseHtml)->toContain('&lt;script&gt;');
});
