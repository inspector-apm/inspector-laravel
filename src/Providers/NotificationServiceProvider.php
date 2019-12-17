<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Notifications to inspect.
     *
     * @var array
     */
    protected $segments = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(NotificationSending::class, function (NotificationSending $event) {
            if ($this->app['inspector']->isRecording()) {
                $segment = $this->app['inspector']
                    ->startSegment('notifications', get_class($event->notification))
                    ->addContext('Data', [
                        'Channel' => $event->channel,
                        'Notifiable' => get_class($event->notifiable),
                    ]);

                $this->segments[$event->notification->id] = $segment;
            }
        });

        $this->app['events']->listen(NotificationSent::class, function (NotificationSent $event) {
            if (array_key_exists($event->notification->id, $this->segments)) {
                $this->segments[$event->notification->id]
                    ->addContext('Response', $event->response)
                    ->end();
            }
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
