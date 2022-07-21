<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Commands;

use Illuminate\Console\Command;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifier;

class NotifySupervisorDownTime extends Command
{
    /** @var string */
    protected $signature = 'supervisor:downtime:notify';

    /** @var string */
    protected $description = 'Notify when supervisor is down.';

    public function handle(): void
    {
        app(SupervisorDowntimeNotifier::class)->notify();
    }
}
