<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;

class DatabaseQueryServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (Inspector::canAddSegments()) {
                $this->handleQueryReport($query->sql, $query->bindings, $query->time, $query->connectionName);
            }
        });
    }

    /**
     * Attach a span to monitor query execution.
     *
     * @param $sql
     * @param array $bindings
     * @param $time
     * @param $connection
     */
    protected function handleQueryReport($sql, array $bindings, $time, $connection)
    {
        $segment = Inspector::startSegment($connection, substr($sql, 0, 50))
            ->start(microtime(true) - $time/1000);

        $context = [
            'connection' => $connection,
            'sql' => $sql,
        ];

        if (config('inspector.bindings')) {
            $context['bindings'] = $bindings;
        }

        $segment->addContext('db', $context)->end($time);
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
