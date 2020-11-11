<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Callbacks;

use Illuminate\Support\Collection;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected;

class OnDownProcesses
{
    /**
     * @param \Illuminate\Support\Collection $downProcesses
     * @param bool $isTesting
     *
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected
     */
    public function __invoke(Collection $downProcesses, bool $isTesting = false)
    {
        // Triggers an exception to make your monitoring tool (Sentry, ...) aware of the problem.
        throw new SupervisorDownProcessesDetected(($isTesting ? (string) __('Exception test:') . ' ' : '')
            . trans_choice(
                '{1}:count supervisor down process has been detected: ":processes".'
                . '|[2,*]:count supervisor down processes have been detected: ":processes".',
                $downProcesses->count(),
                ['processes' => $downProcesses->implode('", "')]
            ));
    }
}
