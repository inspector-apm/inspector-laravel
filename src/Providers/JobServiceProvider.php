<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Inspector\Models\Segment;

class JobServiceProvider extends ServiceProvider
{
    /**
     * Jobs to inspect.
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
        Queue::looping(function () {
            $this->app['inspector']->flush();
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            if($this->app['inspector']->isRecording()){
                // Open a segment if a transaction already exists
                $this->initializeSegment($event->job);
            } else {
                // Start a transaction if there's not one
                $this->app['inspector']->startTransaction($event->job->resolveName())
                    ->addContext('Payload', $event->job->payload());
            }
        });

        $this->app['events']->listen(JobProcessed::class, function (JobProcessed $event) {
            $this->handleJobEnd($event->job);
        });

        $this->app['events']->listen(JobFailed::class, function (JobFailed $event) {
            $this->handleJobEnd($event->job, true);
        });

        $this->app['events']->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event) {
            $this->handleJobEnd($event->job, true);
        });
    }

    protected function initializeSegment(Job $job)
    {
        $segment = $this->app['inspector']
            ->startSegment('job', $job->resolveName())
            ->addContext('payload', $job->payload());

        // Jot down the job with a unique ID
        $this->segments[$this->getJobId($job)] = $segment;
    }

    /**
     * Report job execution to Inspector.
     *
     * @param Job $job
     * @param bool $failed
     */
    public function handleJobEnd(Job $job, $failed = false)
    {
        // If a segment doesn't exists it means that job is registered as transaction
        // we can set the result accordingly
        if (!array_key_exists($this->getJobId($job), $this->segments) && $this->app['inspector']->isRecording()) {
            $this->app['inspector']->currentTransaction()
                ->setResult($failed ? 'error' : 'success');
        } else {
            $this->segments[$this->getJobId($job)]->end();
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
