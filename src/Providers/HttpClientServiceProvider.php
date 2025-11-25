<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

class HttpClientServiceProvider extends ServiceProvider
{
    /**
     * Segments collection.
     *
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(RequestSending::class, function (RequestSending $event) {
            if (Inspector::canAddSegments()) {
                $this->segments[
                    $this->getSegmentKey($event->request)
                ] = Inspector::startSegment("http", $event->request->url());
            }
        });

        $this->app['events']->listen(ResponseReceived::class, function (ResponseReceived $event) {
            $key = $this->getSegmentKey($event->request);

            $type = 'unknown';
            if ($event->request->isForm()) {
                $type = 'form';
            } elseif ($event->request->isJson()) {
                $type = 'json';
            }

            if (\array_key_exists($key, $this->segments)) {
                $this->segments[$key]->end()
                    ->addContext('Url', [
                        'method' => $event->request->method(),
                        'url' => $event->request->url(),
                    ])
                    ->addContext('Request', [
                        'type' => $type,
                        'headers' => $event->request->headers(),
                        'data' => $event->request->data(),
                    ])
                    ->addContext('Response', \array_merge(
                        [
                            'status' => $event->response->status(),
                            'headers' => $event->response->headers(),
                        ],
                        config('inspector.http_client_body') ? ['body' => $event->response->body()] : []
                    ))
                    ->label = $event->response->status() . ' ' .
                        $event->request->method() . ' ' .
                        $event->request->url();
            }
        });
    }

    /**
     * Generate the key to identify the segment in the segment collection.
     *
     * @param Request $request
     * @return string
     */
    protected function getSegmentKey(Request $request)
    {
        return \sha1($request->body());
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
