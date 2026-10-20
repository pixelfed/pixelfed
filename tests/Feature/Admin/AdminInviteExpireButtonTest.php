<?php

use App\Models\AdminInvite;
use App\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin invites Expire button
|--------------------------------------------------------------------------
|
| The invite name is admin-entered free text. It must never reach an inline
| onclick handler, where the browser HTML-decodes the attribute before the JS
| engine parses it (an apostrophe breaks the button, a crafted name executes
| arbitrary JS in the admin session). The name belongs in an HTML-escaped
| data-* attribute, read back into JS via dataset.
|
*/

it('does not interpolate the invite name into an inline onclick handler', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $hostileName = "John's '+alert(document.cookie)+'<script>x</script>";

    $invite = new AdminInvite;
    $invite->name = $hostileName;
    $invite->max_uses = 1;
    $invite->uses = 0;
    $invite->save();

    $response = $this->actingAs($admin)
        ->withoutMiddleware(RequirePassword::class)
        ->get(route('admin.users.invites.index'))
        ->assertOk();

    $response->assertDontSee('onclick="expireInvite(', false);
    $response->assertDontSee("'+alert(document.cookie)+'", false);
    $response->assertDontSee('<script>x</script>', false);

    $response->assertSee('data-name="John&#039;s &#039;+alert(document.cookie)+&#039;&lt;script&gt;x&lt;/script&gt;"', false);
});
