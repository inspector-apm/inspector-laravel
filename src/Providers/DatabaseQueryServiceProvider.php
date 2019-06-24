<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;

class DatabaseQueryServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists(QueryExecuted::class)) {
            $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
                $this->handleQueryReport($query->sql, $query->bindings, $query->time, $query->connectionName);
            });
        } else {
            $this->app['events']->listen('illuminate.query', function ($sql, array $bindings, $time, $connection) {
                $this->handleQueryReport($sql, $bindings, $time, $connection);
            });
        }
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
        if (!$this->app['inspector']->hasTransaction()) {
            return;
        }

        $span = $this->app['inspector']->startSpan($connection);

        $span->getContext()
            ->getDb()
            ->setType($connection)
            ->setSql($sql);

        if (config('inspector.bindings', false)) {
            $span->getContext()->getDb()->setBindings($bindings);
        }

        $span->end($time);
    }
}