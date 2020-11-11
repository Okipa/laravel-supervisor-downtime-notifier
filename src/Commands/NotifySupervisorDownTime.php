<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Commands;

use Illuminate\Console\Command;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifier;

class NotifySupervisorDownTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:downtime:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify when supervisor is down.';

    /**
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\InvalidAllowedToRun
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted
     * @throws \Exception
     */
    public function handle(): void
    {
        app(SupervisorDowntimeNotifier::class)->notify();
    }
}
