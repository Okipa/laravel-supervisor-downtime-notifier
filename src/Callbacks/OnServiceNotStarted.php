<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Callbacks;

use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted;

class OnServiceNotStarted
{
    /** @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted */
    public function __invoke()
    {
        // Triggers an exception to make your monitoring tool (Sentry, ...) aware of the problem.
        throw new SupervisorServiceNotStarted((string) __('Supervisor service is not started.'));
    }
}
