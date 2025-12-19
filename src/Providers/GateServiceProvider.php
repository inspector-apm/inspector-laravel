<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Inspector\Models\Segment;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Illuminate\Contracts\Auth\Authenticatable;

use function md5;
use function array_map;
use function is_string;
use function serialize;
use function is_callable;
use function array_key_exists;

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
     */
    public function beforeGateCheck(Authenticatable $user, string $ability, array $arguments): void
    {
        if (!Inspector::canAddSegments()) {
            return;
        }

        $class = is_string($arguments[0] ?? null) ? $arguments[0] : '';

        $label = "Gate::{$ability}({$class})";

        $this->segments[
            $this->generateUniqueKey($this->formatArguments($arguments))
        ] = Inspector::startSegment('auth.gate', $label)
                ->addContext('user', $user);
    }

    /**
     * Intercepting after gate check.
     *
     * @param bool|Response|null $result
     */
    public function afterGateCheck(Authenticatable $user, string $ability, mixed $result, array $arguments): ?bool
    {
        $isAllowed = $result instanceof Response ? $result->allowed() : $result;

        if (!Inspector::canAddSegments()) {
            return $isAllowed;
        }

        $arguments = $this->formatArguments($arguments);
        $key = $this->generateUniqueKey($this->formatArguments($arguments));

        if (array_key_exists($key, $this->segments)) {
            $this->segments[$key]
                ->addContext('Check', [
                    'ability' => $ability,
                    'result' => $isAllowed ? 'allowed' : 'denied',
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
        return array_map(function (mixed $item) {
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
     */
    public function formatModel(Model $model): string
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
