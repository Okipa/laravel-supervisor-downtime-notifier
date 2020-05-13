<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Test;

use Faker\Factory;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifierServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class FailedJobsNotifierTestCase extends TestCase
{
    protected $faker;

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [SupervisorDowntimeNotifierServiceProvider::class];
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }
}
