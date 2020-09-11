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
use Okipa\LaravelSupervisorDowntimeNotifier\Test\FailedJobsNotifierTestCase;

class SupervisorMonitoringTest extends FailedJobsNotifierTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        NotificationFacade::fake();
    }

    public function testAllowedToRunWithWrongValue()
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', 'test');
        $this->expectException(InvalidAllowedToRun::class);
        (new SupervisorDowntimeNotifier)->isAllowedToRun();
    }

    public function testAllowedToRunWithBoolean()
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', false);
        $allowedToRun = (new SupervisorDowntimeNotifier)->isAllowedToRun();
        $this->assertEquals($allowedToRun, false);
    }

    public function testAllowedToRunWithCallable()
    {
        config()->set('supervisor-downtime-notifier.allowed_to_run', function () {
            return true;
        });
        $allowedToRun = (new SupervisorDowntimeNotifier)->isAllowedToRun();
        $this->assertEquals($allowedToRun, true);
    }

    public function testSetCustomSupervisorChecker()
    {
        config()->set('supervisor-downtime-notifier.supervisor_checker', AnotherSupervisorChecker::class);
        $supervisorChecker = (new SupervisorDowntimeNotifier)->getSupervisorChecker();
        $this->assertInstanceOf(AnotherSupervisorChecker::class, $supervisorChecker);
    }

    public function testSetCustomNotifiable()
    {
        config()->set('supervisor-downtime-notifier.notifiable', AnotherNotifiable::class);
        $notifiable = (new SupervisorDowntimeNotifier)->getNotifiable();
        $this->assertInstanceOf(AnotherNotifiable::class, $notifiable);
    }

    public function testGetCustomServiceNotStartedNotification()
    {
        config()->set(
            'supervisor-downtime-notifier.notifications.service_not_started',
            AnotherServiceNotStarted::class
        );
        $notification = (new SupervisorDowntimeNotifier)->getServiceNotStartedNotification();
        $this->assertInstanceOf(AnotherServiceNotStarted::class, $notification);
    }

    public function testGetCustomDownProcessesNotification()
    {
        config()->set('supervisor-downtime-notifier.notifications.down_processes', AnotherProcessesAreDown::class);
        $notification = (new SupervisorDowntimeNotifier)->getDownProcessesNotification(collect());
        $this->assertInstanceOf(AnotherProcessesAreDown::class, $notification);
    }

    public function testGetCustomServiceNotStartedCallback()
    {
        config()->set('supervisor-downtime-notifier.callbacks.service_not_started', AnotherOnServiceNotStarted::class);
        $callback = (new SupervisorDowntimeNotifier)->getServiceNotStartedCallback();
        $this->assertInstanceOf(AnotherOnServiceNotStarted::class, $callback);
    }

    public function testGetCustomDownProcessesCallback()
    {
        config()->set('supervisor-downtime-notifier.callbacks.down_processes', AnotherOnDownProcesses::class);
        $callback = (new SupervisorDowntimeNotifier)->getDownProcessesCallback();
        $this->assertInstanceOf(AnotherOnDownProcesses::class, $callback);
    }

    public function testNothingHappensWhenNotAllowed()
    {
        $this->partialMock(SupervisorDowntimeNotifier::class, function ($mock) {
            $mock->shouldReceive('monitorSupervisorService')->never();
            $mock->shouldReceive('monitorDownProcesses')->never();
        });
        config()->set('supervisor-downtime-notifier.allowed_to_run', false);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertNothingSent();
    }

    public function testNoNotificationIsSentWhenSupervisorServiceIsRunning()
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(true);
        });
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertNothingSent();
    }

    public function testNotificationIsSentWhenSupervisorServiceIsNotRunning()
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(false);
        });
        config()->set('supervisor-downtime-notifier.callbacks.service_not_started', null);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
        NotificationFacade::assertSentTo(new Notifiable(), ServiceNotStarted::class);
    }

    public function testCallbackIsTriggeredWhenSupervisorServiceIsNotRunning()
    {
        $this->partialMock(SupervisorChecker::class, function ($mock) {
            $mock->shouldReceive('isServiceRunning')->once()->andReturn(false);
        });
        $this->expectException(SupervisorServiceNotStarted::class);
        $this->artisan('supervisor:downtime:notify')->assertExitCode(0);
    }

    public function testNoNotificationIsSentWhenSupervisorProcessesAreUp()
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

    public function testNotificationIsSentWhenSupervisorProcessesAreDown()
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

    public function testCallbackIsTriggeredWhenProcessesAreDown()
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

    public function testDefaultServiceNotStartedMessage()
    {
        $notification = (new SupervisorDowntimeNotifier)->getServiceNotStartedNotification();
        $notifiable = (new SupervisorDowntimeNotifier)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ServiceNotStarted::class,
            function ($notification, $channels) {
                $this->assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // mail
                $mailData = $notification->toMail($channels)->toArray();
                $this->assertEquals('error', $mailData['level']);
                $this->assertEquals('[Laravel - testing] supervisor service is not started', $mailData['subject']);
                $this->assertEquals(
                    'We have detected that the supervisor service is not started on '
                    . '[Laravel - testing](http://localhost).',
                    $mailData['introLines'][0]
                );
                $this->assertEquals(
                    'Please restart you supervisor service connecting to your server and executing the '
                    . '"supervisorctl restart" command line.',
                    $mailData['introLines'][1]
                );
                // slack
                $slackData = $notification->toSlack($channels);
                $this->assertEquals('error', $slackData->level);
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` supervisor service is not started on http://localhost.',
                    $slackData->content
                );
                // webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` supervisor service is not started on http://localhost.',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    public function testDefaultServiceNotStartedCallbackExceptionMessage()
    {
        $callback = (new SupervisorDowntimeNotifier)->getServiceNotStartedCallback();
        $this->expectExceptionMessage('Supervisor service is not started.');
        $callback();
    }

    public function testDefaultProcessesAreDownNotificationSingularMessage()
    {
        $downProcesses = collect(['laravel-queue-testing-worker:*']);
        $notification = (new SupervisorDowntimeNotifier)->getDownProcessesNotification($downProcesses);
        $notifiable = (new SupervisorDowntimeNotifier)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ProcessesAreDown::class,
            function ($notification, $channels) {
                $this->assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // mail
                $mailData = $notification->toMail($channels)->toArray();
                $this->assertEquals('error', $mailData['level']);
                $this->assertEquals(
                    '[Laravel - testing] 1 supervisor down process has been detected',
                    $mailData['subject']
                );
                $this->assertEquals(
                    'We have detected 1 supervisor down process on [Laravel - testing](http://localhost): '
                    . '"laravel-queue-testing-worker:*".',
                    $mailData['introLines'][0]
                );
                $this->assertEquals(
                    'Please check your down processes connecting to your server and executing the '
                    . '"supervisorctl status" command.',
                    $mailData['introLines'][1]
                );
                // slack
                $slackData = $notification->toSlack($channels);
                $this->assertEquals('error', $slackData->level);
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` 1 supervisor down process has been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:*".',
                    $slackData->content
                );
                // webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` 1 supervisor down process has been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:*".',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    public function testDefaultProcessesAreDownNotificationPluralMessage()
    {
        $downProcesses = collect([
            'laravel-queue-testing-worker:process-1',
            'laravel-queue-testing-worker:process-2',
        ]);
        $notification = (new SupervisorDowntimeNotifier)->getDownProcessesNotification($downProcesses);
        $notifiable = (new SupervisorDowntimeNotifier)->getNotifiable();
        $notifiable->notify($notification);
        NotificationFacade::assertSentTo(
            new Notifiable(),
            ProcessesAreDown::class,
            function ($notification, $channels) {
                $this->assertEquals(config('supervisor-downtime-notifier.channels'), $channels);
                // mail
                $mailData = $notification->toMail($channels)->toArray();
                $this->assertEquals('error', $mailData['level']);
                $this->assertEquals(
                    '[Laravel - testing] 2 supervisor down processes have been detected',
                    $mailData['subject']
                );
                $this->assertEquals(
                    'We have detected 2 supervisor down processes on [Laravel - testing](http://localhost): '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $mailData['introLines'][0]
                );
                $this->assertEquals(
                    'Please check your down processes connecting to your server and executing the '
                    . '"supervisorctl status" command.',
                    $mailData['introLines'][1]
                );
                // slack
                $slackData = $notification->toSlack($channels);
                $this->assertEquals('error', $slackData->level);
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` 2 supervisor down processes have been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $slackData->content
                );
                // webhook
                $webhookData = $notification->toWebhook($channels)->toArray();
                $this->assertEquals(
                    '⚠ `[Laravel - testing]` 2 supervisor down processes have been detected on http://localhost: '
                    . '"laravel-queue-testing-worker:process-1", "laravel-queue-testing-worker:process-2".',
                    $webhookData['data']['text']
                );

                return true;
            }
        );
    }

    public function testDefaultDownProcessesCallbackExceptionSingularMessage()
    {
        $downProcesses = collect(['laravel-queue-testing-worker:*']);
        $callback = (new SupervisorDowntimeNotifier)->getDownProcessesCallback();
        $this->expectExceptionMessage('Down supervisor process detected: "laravel-queue-testing-worker:*".');
        $callback($downProcesses);
    }

    public function testDefaultDownProcessesCallbackExceptionPluralMessage()
    {
        $downProcesses = collect([
            'laravel-queue-testing-worker:process-1',
            'laravel-queue-testing-worker:process-2',
        ]);
        $callback = (new SupervisorDowntimeNotifier)->getDownProcessesCallback();
        $this->expectExceptionMessage('Down supervisor processes detected: "laravel-queue-testing-worker:process-1", '
            . '"laravel-queue-testing-worker:process-2".');
        $callback($downProcesses);
    }


    public function testSimulationNotification()
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

    public function testSimulationCallback()
    {
        $this->expectExceptionMessage('Exception test: ');
        $this->artisan(SimulateSupervisorDownTime::class);
    }
}
