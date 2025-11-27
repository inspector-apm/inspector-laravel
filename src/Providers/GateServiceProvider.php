<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_callable;
use function is_string;
use function md5;
use function serialize;

class GateServiceProvider extends ServiceProvider
{
    use FetchesStackTrace;

    /**
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        Gate::before($this->beforeGateCheck(...));
        Gate::after($this->afterGateCheck(...));
    }

    /**
     * Intercepting before gate check.
     *
     * @param \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $ability
     * @param $arguments
     */
    public function beforeGateCheck($user, $ability, $arguments): void
    {
        if (!Inspector::canAddSegments()) {
            return;
        }

        $class = (is_array($arguments) && $arguments !== [])
            ? (is_string($arguments[0]) ? $arguments[0] : '')
            : '';

        $label = "Gate::{$ability}({$class})";

        $this->segments[
            $this->generateUniqueKey($this->formatArguments($arguments))
        ] = Inspector::startSegment('auth.gate', $label)
                ->addContext('user', $user);
    }

    /**
     * Intercepting after gate check.
     *
     * @param  \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  bool  $result
     * @param  array  $arguments
     * @return bool
     */
    public function afterGateCheck($user, $ability, $result, $arguments)
    {
        if (!Inspector::canAddSegments()) {
            return $result;
        }

        $arguments = $this->formatArguments($arguments);
        $key = $this->generateUniqueKey($this->formatArguments($arguments));

        if (array_key_exists($key, $this->segments)) {
            $this->segments[$key]
                ->addContext('Check', [
                    'ability' => $ability,
                    'result' => $result ? 'allowed' : 'denied',
                    'arguments' => $arguments,
                ])
                ->end();

            if ($caller = $this->getCallerFromStackTrace()) {
                $this->segments[$key]
                    ->addContext('Caller', [
                        'file' => $caller['file'],
                        'line' => $caller['line'],
                    ]);
            }
        }

        return $result;
    }

    /**
     * Generate a unique key to track segment's state.
     */
    public function generateUniqueKey(array $data): string
    {
        return md5(serialize($data));
    }

    /**
     * Format gate arguments.
     */
    public function formatArguments(array $arguments): array
    {
        return array_map(function ($item) {
            if ($item instanceof Model) {
                return $this->formatModel($item);
            }

            if (is_callable($item)) {
                return 'callback';
            }

            return $item;
        }, $arguments);
    }

    /**
     * Human-readable model.
     *
     * @param $model
     */
    public function formatModel($model): string
    {
        return $model::class.':'.$model->getKey();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }
}
