<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Commands;

use Illuminate\Console\Command;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifier;

class SimulateSupervisorDownTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:downtime:simulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate supervisor downtime for testing purpose.';

    /** @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected */
    public function handle(): void
    {
        $fakeDownProcesses = collect([[],[]]);
        $notification = (new SupervisorDowntimeNotifier)->getDownProcessesNotification($fakeDownProcesses, true);
        (new SupervisorDowntimeNotifier)->getNotifiable()->notify($notification);
        $onDownProcesses = (new SupervisorDowntimeNotifier)->getDownProcessesCallback();
        if ($onDownProcesses) {
            $onDownProcesses($fakeDownProcesses, true);
        }
    }
}
