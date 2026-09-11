<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if ($mailTo = config('horizon.notification_routing.mail_to')) {
            Horizon::routeMailNotificationsTo($mailTo);
        }

        if ($slackWebhook = config('horizon.notification_routing.slack_webhook_url')) {
            Horizon::routeSlackNotificationsTo(
                $slackWebhook,
                config('horizon.notification_routing.slack_channel')
            );
        }

        if (config('horizon.darkmode') == true) {
            Horizon::night();
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewHorizon', function ($user) {
            return $user->is_admin == true;
        });
    }
}
