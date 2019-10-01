<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Email messages to inspect.
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
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event) {
            if ($this->app['inspector']->isRecording()) {
                $this->segments[$event->message->getId()] = $this->app['inspector']->startSegment('email');
            }
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            if (array_key_exists($event->message->getId(), $this->segments)) {
                $this->segments[$event->message->getId()]->end();
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
