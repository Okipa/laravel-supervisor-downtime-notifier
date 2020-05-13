<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Callbacks;

use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted;

class OnServiceNotStarted
{
    /**
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted
     */
    public function __invoke()
    {
        // triggers an exception to make your monitoring tool (Sentry, ...) aware of the problem.
        throw new SupervisorServiceNotStarted('Supervisor service is not started.');
    }
}
