<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
     * Tells Laravel to render pagination links using Bootstrap 5 styling.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
