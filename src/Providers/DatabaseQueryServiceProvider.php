<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;

use function microtime;

class DatabaseQueryServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query): void {
            if (Inspector::canAddSegments() && $query->sql) {
                $this->handleQueryReport($query->sql, $query->bindings, $query->time, $query->connectionName);
            }
        });
    }

    /**
     * Attach a span to monitor query execution.
     *
     * @param $sql
     * @param $time
     * @param $connection
     */
    protected function handleQueryReport($sql, array $bindings, $time, string $connection): void
    {
        $segment = Inspector::startSegment('db.'.$connection, $sql)
            ->start(microtime(true) - $time / 1000);

        $context = [
            'connection' => $connection,
            'query' => $sql,
        ];

        if (config('inspector.bindings')) {
            $context['bindings'] = $bindings;
        }

        $segment->addContext('db', $context)->end($time);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }
}
