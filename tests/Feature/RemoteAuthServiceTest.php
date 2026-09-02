<?php

namespace Tests\Feature;

use App\Models\RemoteAuthInstance;
use App\Services\Account\RemoteAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoteAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function activeInstance(string $domain = 'mastodon.example'): RemoteAuthInstance
    {
        return RemoteAuthInstance::create([
            'domain' => $domain,
            'client_id' => 'cid',
            'client_secret' => 'secret',
            'redirect_uri' => url('/auth/mastodon/callback'),
            'active' => true,
            'banned' => false,
        ]);
    }

    #[Test]
    public function verify_credentials_returns_false_on_connection_failure()
    {
        $this->activeInstance();

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

        $this->assertFalse($res);
    }

    #[Test]
    public function verify_credentials_returns_false_on_server_error()
    {
        $this->activeInstance();

        Http::fake([
            '*' => Http::response('nope', 500),
        ]);

        $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

        $this->assertFalse($res);
    }

    #[Test]
    public function verify_credentials_returns_json_on_success()
    {
        $this->activeInstance();

        Http::fake([
            '*' => Http::response(['acct' => 'alice', 'id' => '1'], 200),
        ]);

        $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

        $this->assertIsArray($res);
        $this->assertSame('alice', $res['acct']);
    }

    #[Test]
    public function get_following_returns_false_on_connection_failure()
    {
        $this->activeInstance();

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $res = RemoteAuthService::getFollowing('mastodon.example', 'token', 42);

        $this->assertFalse($res);
    }

    #[Test]
    public function get_token_returns_false_on_connection_failure()
    {
        $this->activeInstance();

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $res = RemoteAuthService::getToken('mastodon.example', 'code');

        $this->assertFalse($res);
    }
}
