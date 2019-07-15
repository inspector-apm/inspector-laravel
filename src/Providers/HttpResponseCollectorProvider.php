<?php


namespace Inspector\Laravel\Providers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;

class HttpResponseCollectorProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(RequestHandled::class, function (RequestHandled $event) {
            if(Filters::isApprovedRequest($event->request) && Inspector::isRecording()){
                $this->collectResponseData($event->response);
            }
        });
    }

    /**
     * Add response data to the HTTP transaction.
     *
     * @param \Illuminate\Http\Response $response
     */
    public function collectResponseData($response)
    {
        Inspector::currentTransaction()->setResult('HTTP ' . $response->getStatusCode());
        Inspector::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
        Inspector::currentTransaction()->getContext()->getResponse()->setStatusCode($response->getStatusCode());
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