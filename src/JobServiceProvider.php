<?php


namespace Inspector\Laravel;


use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;

class JobServiceProvider
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
}