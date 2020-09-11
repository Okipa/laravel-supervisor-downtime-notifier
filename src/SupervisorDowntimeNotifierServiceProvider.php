<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier;

use Illuminate\Support\ServiceProvider;
use Okipa\LaravelSupervisorDowntimeNotifier\Commands\NotifySupervisorDownTime;
use Okipa\LaravelSupervisorDowntimeNotifier\Commands\SimulateSupervisorDownTime;

class SupervisorDowntimeNotifierServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([NotifySupervisorDownTime::class, SimulateSupervisorDownTime::class]);
        }
        $this->publishes([
            __DIR__ . '/../config/supervisor-downtime-notifier.php' => config_path('supervisor-downtime-notifier.php'),
        ], 'supervisor-downtime-notifier:config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/supervisor-downtime-notifier.php', 'supervisor-downtime-notifier');
    }
}
