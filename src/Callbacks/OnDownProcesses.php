<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Callbacks;

use Illuminate\Support\Collection;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected;

class OnDownProcesses
{
    /**
     * @param \Illuminate\Support\Collection $downProcesses
     *
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected
     */
    public function __invoke(Collection $downProcesses)
    {
        // triggers an exception to make your monitoring tool (Sentry, ...) aware of the problem.
        throw new SupervisorDownProcessesDetected($downProcesses->count() > 1
            ? 'Down supervisor processes detected: "' . $downProcesses->implode('", "') . '".'
            : 'Down supervisor process detected: "' . $downProcesses->first() . '".');
    }
}
