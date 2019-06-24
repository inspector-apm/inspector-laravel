<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class JobServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::looping(function () {
            $this->app['inspector']->flush();
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            if(!$this->app['inspector']->hasTransaction()){
                $this->app['inspector']->startTransaction($event->job->resolveName());
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