<?php

namespace Tests\Feature;

use App\Models\UserOidcMapping;
use App\Services\UserOidcService;
use App\User;
use Auth;
use Faker\Factory as Faker;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class RemoteOidcTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function view_oidc_start()
    {
        config([
            'remote-auth.oidc.enabled'=> true,
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
    }

    public function view_oidc_callback_new_user()
    {
        $originalUserCount = User::count();
        $this->assertDatabaseCount('users', $originalUserCount);

        config(['remote-auth.oidc.enabled' => true]);

        $oauthData = array(
            "sub" => str_random(10),
            "preferred_username" => fake()->unique()->userName,
            "email" => fake()->unique()->freeEmail,
        );

        $this->partialMock(UserOidcService::class, function (MockInterface $mock) use ($oauthData) {
            $mock->shouldReceive('getAccessToken')->once()->andReturn(new AccessToken(["access_token" => "token" ]));
            $mock->shouldReceive('getResourceOwner')->once()->andReturn(new GenericResourceOwner($oauthData, 'sub'));
            return $mock;
        });

        $response = $this->withoutExceptionHandling()->withSession([
            'oauth2state' => 'abc123',
        ])->get('auth/oidc/callback?state=abc123&code=1');

        $response->assertRedirect('/');

        $mappedUser = UserOidcMapping::where('oidc_id', $oauthData['sub'])->first();
        $this->assertNotNull($mappedUser, "mapping is found");
        $user = $mappedUser->user;
        $this->assertEquals($user->username, $oauthData['preferred_username']);
        $this->assertEquals($user->email, $oauthData['email']);
        $this->assertEquals(Auth::guard()->user()->id, $user->id);

        $this->assertDatabaseCount('users', $originalUserCount+1);
    }

    public function view_oidc_callback_existing_user()
    {
        $user = User::create([
            'name' => fake()->name,
            'username' => fake()->unique()->username,
            'email' => fake()->unique()->freeEmail,
        ]);
        $originalUserCount = User::count();
        $this->assertDatabaseCount('users', $originalUserCount);

        config(['remote-auth.oidc.enabled' => true]);

        $oauthData = array(
            "sub" => str_random(10),
            "preferred_username" => $user->username,
            "email" => $user->email,
        );

        UserOidcMapping::create([
            'oidc_id' => $oauthData['sub'],
            'user_id' => $user->id,
        ]);

        $this->partialMock(UserOidcService::class, function (MockInterface $mock) use ($oauthData) {
            $mock->shouldReceive('getAccessToken')->once()->andReturn(new AccessToken(["access_token" => "token" ]));
            $mock->shouldReceive('getResourceOwner')->once()->andReturn(new GenericResourceOwner($oauthData, 'sub'));
            return $mock;
        });

        $response = $this->withoutExceptionHandling()->withSession([
            'oauth2state' => 'abc123',
        ])->get('auth/oidc/callback?state=abc123&code=1');

        $response->assertRedirect('/');

        $mappedUser = UserOidcMapping::where('oidc_id', $oauthData['sub'])->first();
        $this->assertNotNull($mappedUser, "mapping is found");
        $user = $mappedUser->user;
        $this->assertEquals($user->username, $oauthData['preferred_username']);
        $this->assertEquals($user->email, $oauthData['email']);
        $this->assertEquals(Auth::guard()->user()->id, $user->id);

        $this->assertDatabaseCount('users', $originalUserCount);
    }
}
