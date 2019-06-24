<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Collection of mail event spans.
     *
     * @var array
     */
    protected $spanCollection = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event){
            $this->spanCollection[$event->message->getId()] = $this->app['inspector']->startSpan('email');
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event){
            if(array_key_exists($event->message->getId(), $this->spanCollection)){
                $this->spanCollection[$event->message->getId()]->end();
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