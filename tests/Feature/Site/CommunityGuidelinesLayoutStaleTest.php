<?php

use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Community guidelines page must not bake layout values into cache
|--------------------------------------------------------------------------
|
| communityGuidelines() cached the fully-rendered HTML (layout + footer), so
| config-derived footer values were frozen at cache-warm time. It must cache
| only the page payload and render the layout per request like its siblings.
|
*/

it('re-renders the footer version per request instead of serving a cached blob', function () {
    Page::create([
        'slug' => '/site/kb/community-guidelines',
        'title' => 'Community Guidelines',
        'content' => '<p>Be nice.</p>',
        'active' => true,
    ]);

    // Warm the page with an initial version string.
    Config::set('pixelfed.version', '1.2.3-warm');
    $this->get(route('help.community-guidelines'))
        ->assertOk()
        ->assertSee('v1.2.3-warm');

    // Change the version; the footer (rendered per request) must reflect it
    // rather than serving a footer baked into a cached HTML blob.
    Config::set('pixelfed.version', '9.9.9-fresh');
    $this->get(route('help.community-guidelines'))
        ->assertOk()
        ->assertSee('v9.9.9-fresh')
        ->assertDontSee('v1.2.3-warm');
});
