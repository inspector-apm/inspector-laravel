<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Queue\Job;
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
                if ($this->shouldBeMonitored($event->job->resolveName())) {
                    $this->handleJobStart($event->job);
                }
            }
        );

        $this->app['events']->listen(
            JobProcessed::class,
            function (JobProcessed $event) {
                // If the job fails at the last try it will invoke "JobProcessed" too.
                // This caused "Undefined property $transaction" error.
                if ($this->shouldBeMonitored($event->job->resolveName()) && !$event->job->hasFailed()) {
                    $this->handleJobEnd($event->job);
                }
            }
        );

        $this->app['events']->listen(
            JobFailed::class,
            function (JobFailed $event) {
                if ($this->shouldBeMonitored($event->job->resolveName())) {
                    $this->handleJobEnd($event->job, true);
                }
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
        $id = $this->getJobId($job);

        if (array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        } else {
            Inspector::transaction()
                ->setResult($failed ? 'failed' : 'success');
        }

        // Flush immediately if the job is processed in a long-running process.
        /*
         * We do not have to flush if the application is using the sync driver.
         * In that case the package consider the job as a segment.
         * This can cause the "Undefined property: Inspector\Laravel\Inspector::$transaction" error.
         *
         * https://github.com/inspector-apm/inspector-laravel/issues/21
         */
        if ($this->app->runningInConsole() && config('queue.default') !== 'sync') {
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
