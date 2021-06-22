<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;
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
        // This event is never called in Laravel Vapor.
        /*Queue::looping(
            function () {
                $this->app['inspector']->flush();
            }
        );*/

        $this->app['events']->listen(
            JobProcessing::class,
            function (JobProcessing $event) {
                $this->handleJobStart($event->job);
            }
        );

        $this->app['events']->listen(
            JobProcessed::class,
            function (JobProcessed $event) {
                $this->handleJobEnd($event->job);
            }
        );

        $this->app['events']->listen(
            JobFailed::class,
            function (JobFailed $event) {
                $this->handleJobEnd($event->job, true);
            }
        );

        $this->app['events']->listen(
            JobExceptionOccurred::class,
            function (JobExceptionOccurred $event) {
                $this->handleJobEnd($event->job, true);
            }
        );
    }

    /**
     * Determine the way to monitor the job.
     *
     * @param Job $job
     */
    protected function handleJobStart(Job $job)
    {
        // Ignore job.
        if (!$this->shouldBeMonitored($job->resolveName())) {
            return;
        }

        if (Inspector::needTransaction()) {
            Inspector::startTransaction($job->resolveName())
                ->addContext('Payload', $job->payload());
        } elseif (Inspector::canAddSegments()) {
            $this->initializeSegment($job);
        }
    }

    /**
     * Representing a job as a segment.
     *
     * @param Job $job
     */
    protected function initializeSegment(Job $job)
    {
        $segment = Inspector::startSegment('job', $job->resolveName())
            ->addContext('Payload', $job->payload());

        // Save the job under a unique ID
        $this->segments[$this->getJobId($job)] = $segment;
    }

    /**
     * Finalize the monitoring of the job.
     *
     * @param Job $job
     * @param bool $failed
     */
    public function handleJobEnd(Job $job, $failed = false)
    {
        if (!$this->shouldBeMonitored($job->resolveName())) {
            return;
        }

        $id = $this->getJobId($job);

        if (array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        } else {
            Inspector::currentTransaction()
                ->setResult($failed ? 'error' : 'success');
        }

        // Flush normally happens at shutdown... which only happens in the worker if it is run in a standalone execution.
        // Flush immediately if the job is running in a background worker.
        if ($this->app->runningInConsole()) {
            Inspector::flush();
        }
    }

    /**
     * Get the job ID.
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

    /**
     * Determine if the given job needs to be monitored.
     *
     * @param string $job
     * @return bool
     */
    protected function shouldBeMonitored(string $job): bool
    {
        return Filters::isApprovedJobClass($job, config('inspector.ignore_jobs')) && Inspector::isRecording();
    }
}
