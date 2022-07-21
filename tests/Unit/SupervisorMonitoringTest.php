<?php

namespace Okipa\LaravelSupervisorDowntimeNotifier\Test\Unit;

use DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Okipa\LaravelSupervisorDowntimeNotifier\Commands\SimulateSupervisorDownTime;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\InvalidAllowedToRun;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorDownProcessesDetected;
use Okipa\LaravelSupervisorDowntimeNotifier\Exceptions\SupervisorServiceNotStarted;
use Okipa\LaravelSupervisorDowntimeNotifier\Notifiable;
use Okipa\LaravelSupervisorDowntimeNotifier\Notifications\ProcessesAreDown;
use Okipa\LaravelSupervisorDowntimeNotifier\Notifications\ServiceNotStarted;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorChecker;
use Okipa\LaravelSupervisorDowntimeNotifier\SupervisorDowntimeNotifier;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\AnotherNotifiable;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\AnotherSupervisorChecker;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\Callbacks\AnotherOnDownProcesses;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\Callbacks\AnotherOnServiceNotStarted;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\Notifications\AnotherProcessesAreDown;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\Dummy\Notifications\AnotherServiceNotStarted;
use Okipa\LaravelSupervisorDowntimeNotifier\Test\TestCase;

class SupervisorMonitoringTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        NotificationFacade::fake();
    }

    /** @test */
    public function it_can_check_if_allowed_to_run_with_wrong_config_value(): void
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', 'test');
        $this->expectException(InvalidAllowedToRun::class);
        app(SupervisorDowntimeNotifier::class)->isAllowedToRun();
    }

    /** @test */
    public function it_can_check_if_allowed_to_run_with_boolean_value(): void
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', false);
        $allowedToRun = app(SupervisorDowntimeNotifier::class)->isAllowedToRun();
        self::assertFalse($allowedToRun);
    }

    /** @test */
    public function it_can_check_if_allowed_to_run_with_callable_value(): void
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', function () {
            return true;
        });
        $allowedToRun = app(SupervisorDowntimeNotifier::class)->isAllowedToRun();
        self::assertTrue($allowedToRun);
    }

    /** @test */
    public function it_can_set_custom_notifiable(): void
    {
        config()->set('supervisor-downtime-notifier.supervisor_checker', AnotherSupervisorChecker::class);
        $supervisorChecker = app(SupervisorDowntimeNotifier::class)->getSupervisorChecker();
        self::assertInstanceOf(AnotherSupervisorChecker::class, $supervisorChecker);
    }

    /** @test */
    public function testSetCustomNotifiable(): void
    {
        config()->set('supervisor-downtime-notifier.notifiable', AnotherNotifiable::class);
        $notifiable = app(SupervisorDowntimeNotifier::class)->getNotifiable();
        self::assertInstanceOf(AnotherNotifiable::class, $notifiable);
    }

    /** @test */
    public function it_can_set_custom_service_not_started_notification(): void
    {
        config()->set(
            'supervisor-downtime-notifier.notifications.service_not_started',
            AnotherServiceNotStarted::class
        );
        $notification = app(SupervisorDowntimeNotifier::class)->getServiceNotStartedNotification();
        self::assertInstanceOf(AnotherServiceNotStarted::class, $notification);
    }

    /** @test */
    public function it_can_set_custom_down_processes_notification(): void
    {
        config()->set('supervisor-downtime-notifier.notifications.down_processes', AnotherProcessesAreDown::class);
        $notification = app(SupervisorDowntimeNotifier::class)->getDownProcessesNotification(collect());
        self::assertInstanceOf(AnotherProcessesAreDown::class, $notification);
    }

    /** @test */
    public function it_can_set_custom_service_not_started_callback(): void
    {
        config()->set('supervisor-downtime-notifier.callbacks.service_not_started', AnotherOnServiceNotStarted::class);
        $callback = app(SupervisorDowntimeNotifier::class)->getServiceNotStartedCallback();
        self::assertInstanceOf(AnotherOnServiceNotStarted::class, $callback);
    }

    /** @test */
    public function it_can_set_custom_down_processes_callback(): void
    {
        config()->set('supervisor-downtime-notifier.callbacks.down_processes', AnotherOnDownProcesses::class);
        $callback = app(SupervisorDowntimeNotifier::class)->getDownProcessesCallback();
        self::assertInstanceOf(AnotherOnDownProcesses::class, $callback);
    }

    /** @test */
    public function it_cant_send_notification_when_not_allowed_to(): void
    {
        $this->partialMock(SupervisorDowntimeNotifier::class, function ($mock) {
            $mock->shouldReceive('monitorSupervisorService')->never();
            $mock->shouldReceive('monitorDownProcesses')->never();
        });
        config()->set('supervisor-downtime-notifier.allowed_to_run', false);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertNothingSent();
    }

    /** @test */
    public function it_cant_send_notification_when_service_is_running(): void
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(true);
        });
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertNothingSent();
    }

    /** @test */
    public function it_can_send_notification_when_service_is_not_running(): void
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(false);
        });
        config()->set('supervisor-downtime-notifier.callbacks.service_not_started', null);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertSentTo(new Notifiable(), ServiceNotStarted::class);
    }

    /** @test */
    public function it_can_trigger_callback_when_service_is_not_running(): void
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(false);
        });
        $this->expectException(SupervisorServiceNotStarted::class);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
    }

    /** @test */
    public function it_cant_send_processes_are_down_notification_when_services_are_up(): void
    {
        config()->set('supervisor-downtime-notifier.supervisor', [
            'testing' => ['laravel-queue-testing-worker:*'],
        ]);
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(true);
            $mock->shouldReceive('getDownProcesses')->once()->andReturn(collect());
        });
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertNothingSent();
    }

    /** @test */
    public function it_can_send_notification_when_processes_are_down(): void
    {
        config()->set('supervisor-downtime-notifier.supervisor', [
            'testing' => ['laravel-queue-testing-worker:*'],
        ]);
        config()->set('supervisor-downtime-notifier.callbacks.down_processes', null);
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(true);
            $mock->shouldReceive('getDownProcesses')->once()->andReturn(collect(['laravel-testing-process']));
        });
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertSentTo(new Notifiable(), ProcessesAreDown::class);
    }

    /** @test */
    public function it_can_trigger_callback_when_processes_are_down(): void
    {
        config()->set('supervisor-downtime-notifier.supervisor', [
            'testing' => ['laravel-queue-testing-worker:*'],
        ]);
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(true);
            $mock->shouldReceive('getDownProcesses')->once()->andReturn(collect(['laravel-testing-process']));
        });
        $this->expectException(SupervisorDownProcessesDetected::class);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
    }

    /** @test */
    public function it_can_send_default_service_not_started_notification_message(): void
    {
        $notification = app(SupervisorDowntimeNotifier::class)->getServiceNotStartedNotification();
        $notifiable = app(SupervisorDowntimeNotifier::class)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ServiceNotStarted::class,
            static function ($notification, $channels) {
                self::assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // mail
                $mailData = $notification->toMail($channels)->toArray();
                self::assertEquals('error', $mailData['level']);
                self::assertEquals('[Laravel - testing] supervisor service is not started', $mailData['subject']);
                self::assertEquals(
                    'We have detected that the supervisor service is not started on '
                    . '[Laravel - testing](http://localhost).',
                    $mailData['introLines'][0]
                );
                self::assertEquals(
                    'Please restart you supervisor service connecting to your server and executing the '
                    . '"supervisorctl restart" command line.',
                    $mailData['introLines'][1]
                );
                // Slack
                $slackData = $notification->toSlack($channels);
                self::assertEquals('error', $slackData->level);
                self::assertEquals(
                    '⚠ `[Laravel - testing]` supervisor service is not started on http://localhost.',
                    $slackData->content
                );
                // Webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                self::assertEquals(
                    '⚠ `[Laravel - testing]` supervisor service is not started on http://localhost.',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    /** @test */
    public function it_can_trigger_default_service_not_started_callback_exception_message(): void
    {
        $callback = app(SupervisorDowntimeNotifier::class)->getServiceNotStartedCallback();
        $this->expectExceptionMessage('Supervisor service is not started.');
        $callback();
    }

    /** @test */
    public function it_can_send_default_processes_are_down_singular_notification_message(): void
    {
        $downProcesses = collect(['laravel-queue-testing-worker:*']);
        $notification = app(SupervisorDowntimeNotifier::class)->getDownProcessesNotification($downProcesses);
        $notifiable = app(SupervisorDowntimeNotifier::class)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ProcessesAreDown::class,
            function ($notification, $channels) {
                self::assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // Mail
                $mailData = $notification->toMail($channels)->toArray();
                self::assertEquals('error', $mailData['level']);
                self::assertEquals(
                    '[Laravel - testing] 1 supervisor down process has been detected',
                    $mailData['subject']
                );
                self::assertEquals(
                    'We have detected 1 supervisor down process on [Laravel - testing](http://localhost): '
                    . '"laravel-queue-testing-worker:*".',
                    $mailData['introLines'][0]
                );
                self::assertEquals(
                    'Please check your down processes connecting to your server and executing the '
                    . '"supervisorctl status" command.',
                    $mailData['introLines'][1]
                );
                // Slack
                $slackData = $notification->toSlack($channels);
                self::assertEquals('error', $slackData->level);
                self::assertEquals(
                    '⚠ `[Laravel - testing]` 1 supervisor down process has been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:*".',
                    $slackData->content
                );
                // Webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                self::assertEquals(
                    '⚠ `[Laravel - testing]` 1 supervisor down process has been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:*".',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    /** @test */
    public function it_can_send_default_processes_are_down_plural_notification_message(): void
    {
        $downProcesses = collect([
            'laravel-queue-testing-worker:process-1',
            'laravel-queue-testing-worker:process-2',
        ]);
        $notification = app(SupervisorDowntimeNotifier::class)->getDownProcessesNotification($downProcesses);
        $notifiable = app(SupervisorDowntimeNotifier::class)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ProcessesAreDown::class,
            static function ($notification, $channels) {
                self::assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // Mail
                $mailData = $notification->toMail($channels)->toArray();
                self::assertEquals('error', $mailData['level']);
                self::assertEquals(
                    '[Laravel - testing] 2 supervisor down processes have been detected',
                    $mailData['subject']
                );
                self::assertEquals(
                    'We have detected 2 supervisor down processes on [Laravel - testing](http://localhost): '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $mailData['introLines'][0]
                );
                self::assertEquals(
                    'Please check your down processes connecting to your server and executing the '
                    . '"supervisorctl status" command.',
                    $mailData['introLines'][1]
                );
                // Slack
                $slackData = $notification->toSlack($channels);
                self::assertEquals('error', $slackData->level);
                self::assertEquals(
                    '⚠ `[Laravel - testing]` 2 supervisor down processes have been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $slackData->content
                );
                // Webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                self::assertEquals(
                    '⚠ `[Laravel - testing]` 2 supervisor down processes have been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    /** @test */
    public function it_can_trigger_default_down_processed_callback_exception_singular_message(): void
    {
        $downProcesses = collect(['laravel-queue-testing-worker:*']);
        $callback = app(SupervisorDowntimeNotifier::class)->getDownProcessesCallback();
        $this->expectExceptionMessage('1 supervisor down process has been detected: "laravel-queue-testing-worker:*".');
        $callback($downProcesses);
    }

    /** @test */
    public function it_can_trigger_default_down_processed_callback_exception_plural_message(): void
    {
        $downProcesses = collect([
            'laravel-queue-testing-worker:process-1',
            'laravel-queue-testing-worker:process-2',
        ]);
        $callback = app(SupervisorDowntimeNotifier::class)->getDownProcessesCallback();
        $this->expectExceptionMessage('2 supervisor down processes have been detected: '
            . '"laravel-queue-testing-worker:process-1", '
            . '"laravel-queue-testing-worker:process-2".');
        $callback($downProcesses);
    }

    /** @test */
    public function it_can_simulate_supervisor_downtime_notification(): void
    {
        config()->set('supervisor-downtime-notifier.callbacks.down_processes', null);
        $this->artisan(SimulateSupervisorDownTime::class);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ProcessesAreDown::class,
            function ($notification, $channels) {
                // mail
                $mailData = $notification->toMail($channels)->toArray();
                $this->assertStringContainsString('Notification test: ', $mailData['subject']);
                $this->assertStringContainsString('Notification test: ', $mailData['introLines'][0]);
                // slack
                $slackData = $notification->toSlack($channels);
                $this->assertStringContainsString('Notification test: ', $slackData->content);
                // webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                $this->assertStringContainsString('Notification test: ', $webhookData['data']['text']);

                return true;
            }
        );
    }

    /** @test */
    public function it_can_simulate_supervisor_downtime_exception(): void
    {
        $this->expectExceptionMessage('Exception test: ');
        $this->artisan(SimulateSupervisorDownTime::class);
    }
}
