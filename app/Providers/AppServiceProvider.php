<?php

namespace App\Providers;

use App\Notifications\Channels\LogSmsChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
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
        Notification::resolved(function (ChannelManager $manager): void {
            $manager->extend('sldwp-sms-log', function ($app) {
                return $app->make(LogSmsChannel::class);
             });
        });
    }
}
