<?php

namespace App\Providers;

use App\Models\CustomFilter;
use App\Policies\CustomFilterPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        // Gate::define('viewWebSocketsDashboard', function ($user = null) {
        //     return $user->is_admin;
        // });
    }
}
