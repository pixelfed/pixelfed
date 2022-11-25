<?php

namespace GCS\OIDCClient;

use GCS\OIDCClient\Auth\OIDCGuard;
use GCS\OIDCClient\Auth\OIDCUserProvider;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Jumbojett\OpenIDConnectClient;

class OIDCServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('oidc.php'),
        ]);

        $this->loadRoutesFrom(__DIR__ .'/routes.php');

        Auth::extend('oidc', function($app) {
            $client = $this->createOpenIDConnectClient();
            $provider = new OIDCUserProvider();
            return new OIDCGuard(
                'oidc', $client, $provider, 
                $app['session.store']);
        });
    }

    private function createOpenIDConnectClient()
    {
        $client = new OpenIDConnectClient(
            config('oidc.provider_url'),
            config('oidc.client_id'),
            config('oidc.client_secret')
        );
        foreach (config('oidc.scopes') as $scope) {
            $client->addScope($scope);
        }
        $client->setRedirectURL(
            route('oidc.callback')
        );
        return $client;
    }
    
}
