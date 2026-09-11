<?php

use App\Models\User;
use App\Models\UserOidcMapping;
use App\Services\UserOidcService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Mockery\MockInterface;

uses(LazilyRefreshDatabase::class);

it('shows the oidc start redirect', function () {
    config([
        'remote-auth.oidc.enabled' => true,
        'remote-auth.oidc.clientId' => 'fake',
        'remote-auth.oidc.clientSecret' => 'fakeSecret',
        'remote-auth.oidc.authorizeURL' => 'http://fakeserver.oidc/authorizeURL',
        'remote-auth.oidc.tokenURL' => 'http://fakeserver.oidc/tokenURL',
        'remote-auth.oidc.profileURL' => 'http://fakeserver.oidc/profile',
    ]);
    $response = $this->withoutExceptionHandling()->get('auth/oidc/start');

    $state = session()->get('oauth2state');
    $callbackUrl = urlencode(url('auth/oidc/callback'));

    $response->assertRedirect("http://fakeserver.oidc/authorizeURL?scope=openid%20profile%20email&state={$state}&response_type=code&approval_prompt=auto&redirect_uri={$callbackUrl}&client_id=fake");
});

// it('creates a new user from the oidc callback', function () {
//     $originalUserCount = User::count();
//     $this->assertDatabaseCount('users', $originalUserCount);
//
//     config(['remote-auth.oidc.enabled' => true]);
//
//     $oauthData = [
//         'sub' => Str::random(10),
//         'preferred_username' => fake()->unique()->userName,
//         'email' => fake()->unique()->freeEmail,
//     ];
//
//     $this->partialMock(UserOidcService::class, function (MockInterface $mock) use ($oauthData) {
//         $mock->shouldReceive('getAccessToken')->once()->andReturn(new AccessToken(['access_token' => 'token']));
//         $mock->shouldReceive('getResourceOwner')->once()->andReturn(new GenericResourceOwner($oauthData, 'sub'));
//
//         return $mock;
//     });
//
//     $response = $this->withoutExceptionHandling()->withSession([
//         'oauth2state' => 'abc123',
//     ])->get('auth/oidc/callback?state=abc123&code=1');
//
//     $response->assertRedirect('/');
//
//     $mappedUser = UserOidcMapping::where('oidc_id', $oauthData['sub'])->first();
//     $this->assertNotNull($mappedUser, 'mapping is found');
//     $user = $mappedUser->user;
//     $this->assertEquals($user->username, $oauthData['preferred_username']);
//     $this->assertEquals($user->email, $oauthData['email']);
//     $this->assertEquals(Auth::guard()->user()->id, $user->id);
//
//     $this->assertDatabaseCount('users', $originalUserCount + 1);
// });

// it('maps the oidc callback to an existing user', function () {
//     $user = User::create([
//         'name' => fake()->name,
//         'username' => fake()->unique()->username,
//         'email' => fake()->unique()->freeEmail,
//     ]);
//     $originalUserCount = User::count();
//     $this->assertDatabaseCount('users', $originalUserCount);
//
//     config(['remote-auth.oidc.enabled' => true]);
//
//     $oauthData = [
//         'sub' => Str::random(10),
//         'preferred_username' => $user->username,
//         'email' => $user->email,
//     ];
//
//     UserOidcMapping::create([
//         'oidc_id' => $oauthData['sub'],
//         'user_id' => $user->id,
//     ]);
//
//     $this->partialMock(UserOidcService::class, function (MockInterface $mock) use ($oauthData) {
//         $mock->shouldReceive('getAccessToken')->once()->andReturn(new AccessToken(['access_token' => 'token']));
//         $mock->shouldReceive('getResourceOwner')->once()->andReturn(new GenericResourceOwner($oauthData, 'sub'));
//
//         return $mock;
//     });
//
//     $response = $this->withoutExceptionHandling()->withSession([
//         'oauth2state' => 'abc123',
//     ])->get('auth/oidc/callback?state=abc123&code=1');
//
//     $response->assertRedirect('/');
//
//     $mappedUser = UserOidcMapping::where('oidc_id', $oauthData['sub'])->first();
//     $this->assertNotNull($mappedUser, 'mapping is found');
//     $user = $mappedUser->user;
//     $this->assertEquals($user->username, $oauthData['preferred_username']);
//     $this->assertEquals($user->email, $oauthData['email']);
//     $this->assertEquals(Auth::guard()->user()->id, $user->id);
//
//     $this->assertDatabaseCount('users', $originalUserCount);
// });

it('ensures a valid username from the oidc callback', function () {
    config(['remote-auth.oidc.enabled' => true]);
    config(['remote-auth.oidc.field_username' => 'preferred_username']);

    $dataset = [
        'john.doe@domain.com' => 'johndoe',
        'test+user@part1@domain.com' => 'testuser',
        'user!#$%^&*()_test' => 'user_test',
        'jean-luc.picard' => 'jeanlucpicard',
        'supercalifragilisticexpialidøcious@test.com' => 'supercalifragilisticexpialidci',
        'hélène_renåud' => 'hlne_renud',
        '123456789' => '123456789',
        '  user _ name  ' => 'user_name',
        'foo+bar@sub.domain.co.uk' => 'foobar',
    ];

    foreach ($dataset as $input => $expected) {
        Auth::logout();
        session()->flush();

        $originalUserCount = User::count();

        $oauthData = [
            'sub' => Str::random(10),
            'name' => fake()->name,
            'preferred_username' => $input,
            'email' => fake()->unique()->freeEmail,
        ];

        $this->partialMock(UserOidcService::class, function (MockInterface $mock) use ($oauthData) {
            $mock->shouldReceive('getAccessToken')->once()->andReturn(new AccessToken(['access_token' => 'token']));
            $mock->shouldReceive('getResourceOwner')->once()->andReturn(new GenericResourceOwner($oauthData, 'sub'));
        });

        $response = $this->withoutExceptionHandling()->withSession([
            'oauth2state' => 'abc123',
        ])->get('auth/oidc/callback?state=abc123&code=1');

        $response->assertRedirect('/');

        $mappedUser = UserOidcMapping::where('oidc_id', $oauthData['sub'])->first();

        $this->assertNotNull($mappedUser, "Mapping not found for : {$input}");
        $this->assertEquals($expected, $mappedUser->user->username, "Username not valid : {$input}");
        $this->assertDatabaseCount('users', $originalUserCount + 1);
    }
});
