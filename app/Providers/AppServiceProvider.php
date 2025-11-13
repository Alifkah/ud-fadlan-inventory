<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register helpers jika file exists
        $helperPath = app_path('Helpers/DashboardHelper.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share helper dengan semua views jika class exists
        if (class_exists(\App\Helpers\DashboardHelper::class)) {
            View::composer('*', function ($view) {
                $view->with('DashboardHelper', \App\Helpers\DashboardHelper::class);
            });
        }

        // Set default values untuk pagination
        \Illuminate\Pagination\Paginator::defaultView('pagination::tailwind');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::simple-tailwind');
    }
}