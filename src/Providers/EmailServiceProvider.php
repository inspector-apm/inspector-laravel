<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;
use Symfony\Component\Mime\Email;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function json_encode;
use function sha1;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Segments collection.
     *
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event): void {
            if (Inspector::canAddSegments()) {
                $this->segments[
                    $this->getSegmentKey($event->message)
                ] = Inspector::startSegment('email', $event->message::class)
                        // Compatibility with Laravel 5.5
                        ->addContext(
                            'data',
                            array_intersect_key($event->data, array_flip(['mailer']))
                        );
            }
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event): void {
            $key = $this->getSegmentKey($event->message);

            if (array_key_exists($key, $this->segments)) {
                $this->segments[$key]->end();
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

    /**
     * Generate a unique key for each message.
     */
    protected function getSegmentKey(Email $message): string
    {
        return sha1(json_encode($message->getTo()).$message->getSubject());
    }
}
