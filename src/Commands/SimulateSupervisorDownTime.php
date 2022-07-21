<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Commands;

use Illuminate\Console\Command;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifier;

class SimulateSupervisorDownTime extends Command
{
    /** @var string */
    protected $signature = 'supervisor:downtime:simulate';

    /** @var string */
    protected $description = 'Simulate supervisor downtime for testing purpose.';

    public function handle(): void
    {
        $fakeDownProcesses = collect(['fake-process-1', 'fake-process-2']);
        $notification = app(SupervisorDowntimeNotifier::class)->getDownProcessesNotification($fakeDownProcesses, true);
        app(SupervisorDowntimeNotifier::class)->getNotifiable()->notify($notification);
        $onDownProcesses = app(SupervisorDowntimeNotifier::class)->getDownProcessesCallback();
        if ($onDownProcesses) {
            $onDownProcesses($fakeDownProcesses, true);
        }
    }
}
