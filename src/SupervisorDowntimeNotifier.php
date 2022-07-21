<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier;

use Illuminate\Support\Collection;
use Okipa\LaravelSupervisorDowntimeNotifier\Callbacks\OnDownProcesses;
use Okipa\LaravelSupervisorDowntimeNotifier\Callbacks\OnServiceNotStarted;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\InvalidAllowedToRun;
use Okipa\LaravelSupervisorDowntimeNotifier\Notifications\ProcessesAreDown;
use Okipa\LaravelSupervisorDowntimeNotifier\Notifications\ServiceNotStarted;

class SupervisorDowntimeNotifier
{
    /**
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\InvalidAllowedToRun
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted
     * @throws \Exception
     */
    public function notify(): void
    {
        if ($this->isAllowedToRun()) {
            $this->monitorSupervisorService();
            $this->monitorDownProcesses();
        }
    }

    /** @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\InvalidAllowedToRun */
    public function isAllowedToRun(): bool
    {
        $allowedToRun = config('supervisor-downtime-notifier.allowed_to_run');
        if (is_callable($allowedToRun)) {
            return $allowedToRun();
        }
        if (is_bool($allowedToRun)) {
            return $allowedToRun;
        }
        throw new InvalidAllowedToRun('The `supervisor-downtime-notifier.allowed_to_run` config is not a '
            . 'boolean or a callable.');
    }

    /** @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted */
    public function monitorSupervisorService(): void
    {
        if (! $this->getSupervisorChecker()->isServiceRunning()) {
            $this->getNotifiable()->notify($this->getServiceNotStartedNotification());
            $onServiceNotStarted = $this->getServiceNotStartedCallback();
            if ($onServiceNotStarted) {
                $onServiceNotStarted();
            }
        }
    }

    public function getSupervisorChecker(): SupervisorChecker
    {
        return app(config('supervisor-downtime-notifier.supervisor_checker'));
    }

    public function getNotifiable(): Notifiable
    {
        return app(config('supervisor-downtime-notifier.notifiable'));
    }

    public function getServiceNotStartedNotification(): ServiceNotStarted
    {
        return app(config('supervisor-downtime-notifier.notifications.service_not_started'));
    }

    public function getServiceNotStartedCallback(): ?OnServiceNotStarted
    {
        $callbackClass = config('supervisor-downtime-notifier.callbacks.service_not_started');

        return $callbackClass ? app($callbackClass) : null;
    }

    /**
     * @throws \Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected
     * @throws \Exception
     */
    public function monitorDownProcesses(): void
    {
        $envSupervisorProcessConfig = $this->getEnvSupervisorProcessesConfig();
        if (! $envSupervisorProcessConfig) {
            return;
        }
        $downProcesses = $this->getSupervisorChecker()->getDownProcesses($envSupervisorProcessConfig);
        if ($downProcesses->isNotEmpty()) {
            $this->getNotifiable()->notify($this->getDownProcessesNotification($downProcesses));
            $onDownProcesses = $this->getDownProcessesCallback();
            if ($onDownProcesses) {
                $onDownProcesses($downProcesses);
            }
        }
    }

    public function getEnvSupervisorProcessesConfig(): ?array
    {
        return config('supervisor-downtime-notifier.supervisor.' . app()->environment());
    }

    public function getDownProcessesNotification(Collection $downProcesses, bool $isTesting = false): ProcessesAreDown
    {
        return app(
            config('supervisor-downtime-notifier.notifications.down_processes'),
            compact('downProcesses', 'isTesting')
        );
    }

    public function getDownProcessesCallback(): ?OnDownProcesses
    {
        $callbackClass = config('supervisor-downtime-notifier.callbacks.down_processes');

        return $callbackClass ? app($callbackClass) : null;
    }
}
