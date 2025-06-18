<?php


namespace Inspector\Laravel\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

class GateServiceProvider extends ServiceProvider
{
    use FetchesStackTrace;

    /**
     * @var Segment[]
     */
    protected array $segments = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before([$this, 'beforeGateCheck']);
        Gate::after([$this, 'afterGateCheck']);
    }

    /**
     * Intercepting before gate check.
     *
     * @param \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $ability
     * @param $arguments
     */
    public function beforeGateCheck($user, $ability, $arguments)
    {
        if (!Inspector::canAddSegments()) {
            return;
        }

        $class = (\is_array($arguments)&&!empty($arguments))
            ? (\is_string($arguments[0]) ? $arguments[0] : '')
            : '';

        $label = "Gate::{$ability}({$class})";

        $this->segments[
            $this->generateUniqueKey($this->formatArguments($arguments))
        ] = Inspector::startSegment('gate', $label)
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
     *
     * @param array $data
     * @return string
     */
    public function generateUniqueKey(array $data)
    {
        return \md5(\serialize($data));
    }

    /**
     * Format gate arguments.
     *
     * @param array $arguments
     * @return array
     */
    public function formatArguments(array $arguments)
    {
        return \array_map(function ($item) {
            if ($item instanceof Model) {
                return $this->formatModel($item);
            }

            if (\is_callable($item)) {
                return 'callback';
            }

            return $item;
        }, $arguments);
    }

    /**
     * Human-readable model.
     *
     * @param $model
     * @return string
     */
    public function formatModel($model)
    {
        return \get_class($model).':'.$model->getKey();
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
