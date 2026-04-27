<?php

namespace App\Providers;

use App\Facades\AppMethods;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App',function(){
            return new AppMethods();
        });

        $this->app->bind(
            \App\Services\Sms\SmsProviderInterface::class,
            \App\Services\Sms\LogSmsProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
