<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        //error_reporting(-1); // Reports everything
        //error_reporting(0); // Reports nothing?
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        //from https://www.godaddy.com/garage/webpro/development/suppressing-warning-messages-php-deprecated-functions/
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
