<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

use function array_key_exists;
use function is_string;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Notifications to inspect.
     *
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(NotificationSending::class, function (NotificationSending $event): void {
            if (Inspector::canAddSegments()) {
                $this->segments[$event->notification->id] =
                    Inspector::startSegment('notification', $event->notification::class)
                        ->addContext('data', [
                            'Channel' => $event->channel,
                            'Notifiable' => is_string($event->notifiable) ? $event->notifiable : $event->notifiable::class,
                        ]);
            }
        });

        $this->app['events']->listen(NotificationSent::class, function (NotificationSent $event): void {
            if (array_key_exists($event->notification->id, $this->segments)) {
                $this->segments[$event->notification->id]
                    ->addContext('Response', $event->response)
                    ->end();
            }
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }
}
