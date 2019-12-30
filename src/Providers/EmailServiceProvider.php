<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\ServiceProvider;
use Inspector\Models\Segment;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Segments to monitor.
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
            if ($this->app['inspector']->isRecording()) {
                $this->segments[$this->generateKey($event->data)] = $this->app['inspector']
                    ->startSegment('email', get_class($event->message))
                    ->addContext('data', $event->data);
            }
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            $key = $this->generateKey($event->data);

            if (array_key_exists($key, $this->segments)) {
                $this->segments[$key]->end();
            }
        });
    }

    /**
     * Generate a unique key for a segment.
     *
     * @param array $data
     * @return string
     */
    protected function generateKey($data): string
    {
        return md5(json_encode($data));
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
