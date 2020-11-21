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
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if(config('pixelfed.oauth_enabled')) {
            Passport::routes(null, ['middleware' => ['twofactor', \Fruitcake\Cors\HandleCors::class]]);
            Passport::tokensExpireIn(now()->addDays(15));
            Passport::refreshTokensExpireIn(now()->addDays(30));
            Passport::enableImplicitGrant();
            if(config('instance.oauth.pat.enabled')) {
                Passport::personalAccessClientId(config('instance.oauth.pat.id'));
            }
            Passport::setDefaultScope([
                'read',
                'write',
                'follow',
            ]);

            Passport::tokensCan([
                'read' => 'Full read access to your account',
                'write' => 'Full write access to your account',
                'follow' => 'Ability to follow other profiles',
                'push'  => ''
            ]);
        }

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $user->is_admin;
        });
    }
}
