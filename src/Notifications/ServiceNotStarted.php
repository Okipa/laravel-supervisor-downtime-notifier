<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Webhook\WebhookMessage;

class ServiceNotStarted extends Notification
{
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

    /**
     * Get the slack representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack(): SlackMessage
    {
        return (new SlackMessage())->error()
            ->content('⚠ ' . (string) __('`[:app - :env]` supervisor service is not started on :url.', [
                    'app' => config('app.name'),
                    'env' => config('app.env'),
                    'url' => config('app.url'),
                ]));
    }

    /**
     * Get the webhook representation of the notification.
     *
     * @return \NotificationChannels\Webhook\WebhookMessage
     */
    public function toWebhook(): WebhookMessage
    {
        // rocket chat webhook example
        return WebhookMessage::create()->data([
            'text' => '⚠ ' . (string) __('`[:app - :env]` supervisor service is not started on :url.', [
                    'app' => config('app.name'),
                    'env' => config('app.env'),
                    'url' => config('app.url'),
                ]),
        ])->header('Content-Type', 'application/json');
    }
}
