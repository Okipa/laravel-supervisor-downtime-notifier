<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SupervisorChecker
{
    /**
     * @param array $envSupervisorProcessConfig
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function getDownProcesses(array $envSupervisorProcessConfig): Collection
    {
        $downProcesses = [];
        $sudo = $envSupervisorProcessConfig['sudo'] ? '$(which sudo) ' : '';
        foreach ($envSupervisorProcessConfig['processes'] as $supervisorProcess) {
            $command = $sudo . '$(which supervisorctl) status "' . $supervisorProcess . '"';
            $shellProcess = Process::fromShellCommandline($command);
            $shellProcess->run();
            if (! $shellProcess->isSuccessful()) {
                throw new ProcessFailedException($shellProcess);
            }
            $output = $shellProcess->getOutput();
            $processIsDown = Str::contains($output, [
                // http://supervisord.org/subprocess.html#process-states
                'STOPPED',
                'BACKOFF',
                'STOPPING',
                'EXITED',
                'FATAL',
                'UNKNOWN',
                'ERROR',
            ]);
            if ($processIsDown) {
                $downProcesses[] = $supervisorProcess;
            }
        }

        return collect($downProcesses);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isServiceRunning(): bool
    {
        $command = '$(which systemctl) is-active --quiet supervisor';
        $shellProcess = Process::fromShellCommandline($command);
        $shellProcess->run();

        return $shellProcess->isSuccessful();
    }
}
