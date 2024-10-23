<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Segments collection.
     *
     * @var Segment[]
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
            if (Inspector::canAddSegments()) {
                $this->segments[
                    $this->getSegmentKey($event->message)
                ] = Inspector::startSegment('email', get_class($event->message))
                        // Compatibility with Laravel 5.5
                        ->addContext(
                            'data',
                            \property_exists($event, 'data')
                                    ? \array_intersect_key($event->data, \array_flip( ['mailer'] ) )
                                    : []
                        );
            }
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            $key = $this->getSegmentKey($event->message);

            if (\array_key_exists($key, $this->segments)) {
                $this->segments[$key]->end();
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

    /**
     * Generate a unique key for each message.
     *
     * @param \Swift_Message|\Symfony\Component\Mime\Email $message
     * @return string
     */
    protected function getSegmentKey($message)
    {
        return \sha1(\json_encode($message->getTo()).$message->getSubject());
    }
}
