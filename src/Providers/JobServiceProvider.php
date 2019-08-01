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
    protected $jobs = [];

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
            // Jot down the job with a unique ID
            $this->jobs[$this->getJobId($event->job)] = [
                'name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'started_at' => microtime(true),
                'payload' => $event->job->payload(),
            ];
        });

        $this->app['events']->listen(JobProcessed::class, function (JobProcessed $event) {
            $this->reportJob($event->job);
        });

        $this->app['events']->listen(JobFailed::class, function (JobFailed $event) {
            $this->reportJob($event->job, true);
        });

        $this->app['events']->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event) {
            $this->app['inspector']->reportException($event->exception);

            $this->reportJob($event->job, true);
        });
    }

    /**
     * Report job execution to Inspector.
     *
     * @param Job $job
     * @param bool $failed
     */
    public function reportJob(Job $job, $failed = false)
    {
        if(!array_key_exists($this->getJobId($job), $this->jobs)){
            return;
        }

        $item = $this->jobs[$this->getJobId($job)];

        $this->app['inspector']->startSegment('job')
            ->setLabel($item['name'])
            ->start($item['started_at'])
            ->setContext($item['payload'])
            ->end(microtime(true) - $item['started_at']);
    }

    /**
     * Get Job ID.
     *
     * @param  Job $job
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
