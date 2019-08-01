<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class JobServiceProvider extends ServiceProvider
{
    /**
     * Current Jobs.
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
        Queue::looping(function () {
            $this->app['inspector']->flush();

            $this->app['inspector']->startTransaction(implode(' ', $_SERVER['argv']));
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            $segment = $this->app['inspector']
                ->startSegment('job')
                ->setLabel($event->job->resolveName())
                ->setContext($event->job->payload());

            // Jot down the job with a unique ID
            $this->segments[$this->getJobId($event->job)] = $segment;
        });

        $this->app['events']->listen(JobProcessed::class, function (JobProcessed $event) {
            $this->handleJobEnd($event->job);
        });

        $this->app['events']->listen(JobFailed::class, function (JobFailed $event) {
            $this->handleJobEnd($event->job, true);
        });

        $this->app['events']->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event) {
            $this->app['inspector']->reportException($event->exception);

            $this->handleJobEnd($event->job, true);
        });
    }

    /**
     * Report job execution to Inspector.
     *
     * @param Job $job
     * @param bool $failed
     */
    public function handleJobEnd(Job $job, $failed = false)
    {
        if (!array_key_exists($this->getJobId($job), $this->segments)) {
            return;
        }

        $this->segments[$this->getJobId($job)]->end();

        if($failed){
            $this->app['inspector']->currentTransaction()->setResult('error');
        }
    }

    /**
     * Get Job ID.
     *
     * @param Job $job
     * @return string|int
     */
    public static function getJobId(Job $job)
    {
        if ($jobId = $job->getJobId()) {
            return $jobId;
        }

        return sha1($job->getRawBody());
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
