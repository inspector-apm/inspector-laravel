<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobReleasedAfterException;
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
                if (config('inspector.enable') && !Inspector::isRecording()) {
                    Inspector::startRecording();
                }

                if ($this->shouldBeMonitored($event->job->resolveName())) {
                    $this->handleJobStart($event->job);
                }
            }
        );

        $this->app['events']->listen(
            JobExceptionOccurred::class,
            function (JobExceptionOccurred $event) {
                // An unhandled exception will be reported by the ExceptionServiceProvider in case of a sync execution.
                if (Inspector::canAddSegments() && $event->job->getConnectionName() !== 'sync') {
                    Inspector::reportException($event->exception, false);
                }
            }
        );

        $this->app['events']->listen(
            JobProcessed::class,
            function ($event) {
                if ($this->shouldBeMonitored($event->job->resolveName()) && Inspector::isRecording()) {
                    $this->handleJobEnd($event->job);
                }
            }
        );

        if (version_compare(app()->version(), '9.0.0', '>=')) {
            $this->app['events']->listen(
                JobReleasedAfterException::class,
                function (JobReleasedAfterException $event) {
                    if ($this->shouldBeMonitored($event->job->resolveName()) && Inspector::isRecording()) {
                        $this->handleJobEnd($event->job, true);

                        // Laravel throws the current exception after raising the failed events.
                        // So after flushing, we turn off the monitoring to avoid ExceptionServiceProvider will report
                        // the exception again causing a new transaction to start.
                        // We'll restart recording in the JobProcessing event at the start of the job lifecycle
                        if ($event->job->getConnectionName() !== 'sync') {
                            Inspector::stopRecording();
                        }
                    }
                }
            );
        }

        $this->app['events']->listen(
            JobFailed::class,
            function (JobFailed $event) {
                if ($this->shouldBeMonitored($event->job->resolveName()) && Inspector::isRecording()) {
                    // JobExceptionOccurred event is called after JobFailed, so we have to report the exception here.
                    Inspector::reportException($event->exception, false);

                    $this->handleJobEnd($event->job, true);

                    // Laravel throws the current exception after raising the failed events.
                    // So after flushing, we turn off the monitoring to avoid ExceptionServiceProvider will report
                    // the exception again causing a new transaction to start.
                    // We'll restart recording in the JobProcessing event at the start of the job lifecycle
                    if ($event->job->getConnectionName() !== 'sync') {
                        Inspector::stopRecording();
                    }
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
                ->setType('job')
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

        /*
         * Flush immediately if the job is processed in a long-running process.
         *
         * We do not have to flush if the application is using the sync driver.
         * In that case, the package considers the job as a segment.
         */
        if ($job->getConnectionName() !== 'sync') {
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
        return Filters::isApprovedJobClass($job, config('inspector.ignore_jobs'));
    }
}
