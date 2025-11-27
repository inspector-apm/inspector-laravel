<?php

declare(strict_types=1);

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

use function array_key_exists;
use function sha1;
use function version_compare;

class JobServiceProvider extends ServiceProvider
{
    /**
     * Jobs to inspect.
     *
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        // This event is never called in Laravel Vapor.
        /*Queue::looping(
            function () {
                $this->app['inspector']->flush();
            }
        );*/

        $this->app['events']->listen(
            JobProcessing::class,
            function (JobProcessing $event): void {
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
            function (JobExceptionOccurred $event): void {
                // An unhandled exception will be reported by the ExceptionServiceProvider in case of a sync execution.
                if (Inspector::canAddSegments() && $event->job->getConnectionName() !== 'sync') {
                    Inspector::reportException($event->exception, false);
                }
            }
        );

        $this->app['events']->listen(
            JobProcessed::class,
            function ($event): void {
                if ($this->shouldBeMonitored($event->job->resolveName()) && Inspector::isRecording()) {
                    $this->handleJobEnd($event->job);
                }
            }
        );

        if (version_compare(app()->version(), '9.0.0', '>=')) {
            $this->app['events']->listen(
                JobReleasedAfterException::class,
                function (JobReleasedAfterException $event): void {
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
            function (JobFailed $event): void {
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
     */
    protected function initializeSegment(Job $job)
    {
        $payload = $job->payload();

        if (!config('inspector.job_data') && array_key_exists('data', $payload)) {
            unset($payload['data']);
        }

        $segment = Inspector::startSegment('job', $job->resolveName())
            ->addContext('Payload', $payload);

        // Save the job under a unique ID
        $this->segments[static::getJobId($job)] = $segment;
    }

    /**
     * Finalize the monitoring of the job.
     */
    public function handleJobEnd(Job $job, bool $failed = false): void
    {
        $id = static::getJobId($job);

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
     */
    public function register(): void
    {
        //
    }

    /**
     * Determine if the given job needs to be monitored.
     */
    protected function shouldBeMonitored(string $job): bool
    {
        return Filters::isApprovedClass($job, config('inspector.ignore_jobs'));
    }
}
