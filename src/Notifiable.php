<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class Notifiable
{
    use NotifiableTrait;

    public function routeNotificationForMail(): string
    {
        return config('supervisor-downtime-notifier.mail.to');
    }

    public function routeNotificationForSlack(): string
    {
        return config('supervisor-downtime-notifier.slack.webhookUrl');
    }

    public function routeNotificationForWebhook(): string
    {
        return config('supervisor-downtime-notifier.webhook.url');
    }

    public function getKey(): int
    {
        return 1;
    }
}
