<?php

namespace App\Providers;

use App\Http\Controllers\Auth\SupabaseUserProvider;
use App\Services\N8nService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

public function register()
{
    $this->app->singleton(SupabaseService::class, function ($app) {
        return new SupabaseService();
    });

    $this->app->singleton(N8nService::class, function ($app) {
        return new N8nService();
    });
}

    /**
     * Bootstrap any application services.
     */


public function boot()
{
    Auth::provider('supabase', function ($app, array $config) {
        // Laravel akan inject container $app untuk resolve SupabaseService
        return new SupabaseUserProvider($app->make(SupabaseService::class));
    });
}

}
