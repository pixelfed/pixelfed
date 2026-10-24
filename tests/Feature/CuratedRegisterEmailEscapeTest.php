<?php

use App\Mail\CuratedRegisterNotifyAdmin;
use App\Mail\CuratedRegisterNotifyAdminUserResponse;
use App\Mail\CuratedRegisterRequestDetailsFromUser;
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

/*
| request-details-from-user is rendered as an HTTP response by the admin
| previewDetailsMessageShow endpoint, which reflects a GET `message` param into
| $activity->message. It must escape the message to prevent reflected XSS, the
| same way its sibling admin templates do.
*/
it('escapes the message in the request-details-from-user mailable', function () {
    $verify = new CuratedRegister;
    $verify->username = 'attacker';
    $verify->email = 'attacker@example.com';
    $verify->save();

    $activity = new CuratedRegisterActivity;
    $activity->register_id = $verify->id;
    $activity->message = '<img src=x onerror=alert(1)>';
    $activity->save();

    $detailsHtml = (new CuratedRegisterRequestDetailsFromUser($verify, $activity))->render();

    // The raw <img> tag and its onerror handler must not survive as live HTML.
    expect($detailsHtml)->not->toContain('<img src=x onerror=');
    expect($detailsHtml)->toContain('&lt;img src=x onerror=alert(1)&gt;');
});
