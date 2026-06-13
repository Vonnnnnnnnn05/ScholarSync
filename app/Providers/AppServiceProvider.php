<?php

namespace App\Providers;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Observers\CertificateObserver;
use App\Observers\CertificateRequestObserver;
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
        Certificate::observe(CertificateObserver::class);
    }
}
