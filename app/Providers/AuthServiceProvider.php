<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        if(config('app.env') === 'production' && config('pixelfed.oauth_enabled') == true) {
            Passport::tokensExpireIn(now()->addDays(config('instance.oauth.token_expiration', 356)));
            Passport::refreshTokensExpireIn(now()->addDays(config('instance.oauth.refresh_expiration', 400)));
            Passport::enableImplicitGrant();
            if(config('instance.oauth.pat.enabled')) {
                Passport::personalAccessClientId(config('instance.oauth.pat.id'));
            }

            Passport::tokensCan([
                'read' => 'Full read access to your account',
                'write' => 'Full write access to your account',
                'follow' => 'Ability to follow other profiles',
                'admin:read' => 'Read all data on the server',
                'admin:write' => 'Modify all data on the server',
                'push'  => 'Receive your push notifications'
            ]);

            Passport::setDefaultScope([
                'read',
                'write',
                'follow',
            ]);
        }

        // Gate::define('viewWebSocketsDashboard', function ($user = null) {
        //     return $user->is_admin;
        // });
    }
}
