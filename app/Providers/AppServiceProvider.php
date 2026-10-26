<?php

namespace App\Providers;

use App\Models\CertificateRequest;
use App\Observers\CertificateRequestObserver;
use App\Services\NavigationNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CertificateRequest::observe(CertificateRequestObserver::class);

        View::composer(['layouts.sidebar', 'layouts.navigation'], function ($view) {
            $counts = Auth::check()
                ? app(NavigationNotificationService::class)->getCounts(Auth::user())
                : [];

            $view->with('navNotificationCounts', $counts);
        });
    }
}
