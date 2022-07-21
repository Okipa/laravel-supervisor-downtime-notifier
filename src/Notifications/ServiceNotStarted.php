<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Webhook\WebhookMessage;

class ServiceNotStarted extends Notification
{
    public function via(): array
    {
        return config('supervisor-downtime-notifier.channels');
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())->level('error')
            ->subject((string) __('[:app - :env] supervisor service is not started', [
                'app' => config('app.name'),
                'env' => config('app.env'),
            ]))
            ->line((string) __('We have detected that the supervisor service is not started on [:app - :env](:url).', [
                'app' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
            ]))
            ->line((string) __('Please restart you supervisor service connecting to your server and executing the '
                . '"supervisorctl restart" command line.'));
    }

    public function toSlack(): SlackMessage
    {
        return (new SlackMessage())->error()
            ->content('⚠ ' . (string) __('`[:app - :env]` supervisor service is not started on :url.', [
                    'app' => config('app.name'),
                    'env' => config('app.env'),
                    'url' => config('app.url'),
                ]));
    }

    public function toWebhook(): WebhookMessage
    {
        // Rocket chat webhook example.
        return WebhookMessage::create()->data([
            'text' => '⚠ ' . (string) __('`[:app - :env]` supervisor service is not started on :url.', [
                    'app' => config('app.name'),
                    'env' => config('app.env'),
                    'url' => config('app.url'),
                ]),
        ])->header('Content-Type', 'application/json');
    }
}
