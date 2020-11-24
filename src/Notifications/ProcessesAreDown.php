<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\Webhook\WebhookMessage;

class ProcessesAreDown extends Notification
{
    protected Collection $downProcesses;

    protected bool $isTesting;

    protected int $processesCount;

    public function __construct(Collection $downProcesses, bool $isTesting = false)
    {
        $this->downProcesses = $downProcesses;
        $this->isTesting = $isTesting;
        $this->processesCount = $downProcesses->count();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        return config('supervisor-downtime-notifier.channels');
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage())->level('error')
            ->subject(($this->isTesting ? __('Notification test:') . ' ' : '')
                . trans_choice(
                    '{1}[:app - :env] :count supervisor down process has been detected'
                    . '|[2,*][:app - :env] :count supervisor down processes have been detected',
                    $this->processesCount,
                    [
                        'app' => config('app.name'),
                        'env' => config('app.env'),
                        'count' => $this->processesCount,
                    ]
                ))
            ->line(($this->isTesting ? __('Notification test:') . ' ' : '')
                . trans_choice(
                    '{1}We have detected :count supervisor down process on [:app - :env](:url): ":processes".'
                    . '|[2,*]We have detected :count supervisor down processes on [:app - :env](:url): ":processes".',
                    $this->processesCount,
                    [
                        'count' => $this->processesCount,
                        'app' => config('app.name'),
                        'env' => config('app.env'),
                        'url' => config('app.url'),
                        'processes' => $this->downProcesses->implode('", "'),
                    ]
                ))
            ->line((string) __('Please check your down processes connecting to your server and executing the '
                . '"supervisorctl status" command.'));
    }

    /**
     * Get the slack representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack(): SlackMessage
    {
        return (new SlackMessage())->error()->content('⚠ '
            . ($this->isTesting ? __('Notification test:') . ' ' : '')
            . trans_choice(
                '{1}`[:app - :env]` :count supervisor down process has been detected on :url: ":processes".'
                . '|[2,*]`[:app - :env]` :count supervisor down processes have been detected on :url: ":processes".',
                $this->processesCount,
                [
                    'app' => config('app.name'),
                    'env' => config('app.env'),
                    'count' => $this->processesCount,
                    'url' => config('app.url'),
                    'processes' => $this->downProcesses->implode('", "'),
                ]
            ));
    }

    /**
     * Get the webhook representation of the notification.
     *
     * @return \NotificationChannels\Webhook\WebhookMessage
     */
    public function toWebhook(): WebhookMessage
    {
        // Rocket chat webhook example.
        return WebhookMessage::create()->data([
            'text' => '⚠ '
                . ($this->isTesting ? __('Notification test:') . ' ' : '')
                . trans_choice(
                    '{1}`[:app - :env]` :count supervisor down process has been detected on :url: ":processes".'
                    . '|[2,*]`[:app - :env]` :count supervisor down processes have been detected on :url: '
                    . '":processes".',
                    $this->processesCount,
                    [
                        'app' => config('app.name'),
                        'env' => config('app.env'),
                        'count' => $this->processesCount,
                        'url' => config('app.url'),
                        'processes' => $this->downProcesses->implode('", "'),
                    ]
                ),
        ])->header('Content-Type', 'application/json');
    }
}
