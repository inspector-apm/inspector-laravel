<?php


namespace Inspector\Laravel;


use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class EmailServiceProvider
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
}