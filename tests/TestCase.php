<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Test;

use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifierServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SupervisorDowntimeNotifierServiceProvider::class];
    }
}
