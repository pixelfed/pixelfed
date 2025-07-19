<?php

namespace App\Providers;

use App\Models\CustomFilter;
use App\Policies\CustomFilterPolicy;
use Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        CustomFilter::class => CustomFilterPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('pixelfed.oauth_enabled') == true) {
            Passport::ignoreRoutes();
            Passport::tokensExpireIn(now()->addDays(config('instance.oauth.token_expiration', 356)));
            Passport::refreshTokensExpireIn(now()->addDays(config('instance.oauth.refresh_expiration', 400)));
            Passport::enableImplicitGrant();
            if (config('instance.oauth.pat.enabled')) {
                Passport::personalAccessClientId(config('instance.oauth.pat.id'));
            }

            Passport::tokensCan([
                'read' => 'Full read access to your account',
                'write' => 'Full write access to your account',
                'follow' => 'Ability to follow other profiles',
                'admin:read' => 'Read all data on the server',
                'admin:read:domain_blocks' => 'Read sensitive information of all domain blocks',
                'admin:write' => 'Modify all data on the server',
                'admin:write:domain_blocks' => 'Perform moderation actions on domain blocks',
                'push' => 'Receive your push notifications',
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
