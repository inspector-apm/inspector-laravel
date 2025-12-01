<?php

declare(strict_types=1);

namespace Inspector\Laravel\Views;

use Illuminate\Contracts\View\Engine;
use Illuminate\View\Factory;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;

use function basename;
use function call_user_func_array;

final readonly class ViewEngineDecorator implements Engine
{
    public const SHARED_KEY = '__inspector_view_name';

    public function __construct(
        private Engine $engine,
        private Factory $viewFactory
    ) {
    }

    /**
     * {@inheritdoc}
     * @param string $path
     */
    public function get(mixed $path, array $data = []): mixed
    {
        if (!Inspector::canAddSegments()) {
            return $this->engine->get($path, $data);
        }

        $label = 'view::'.$this->viewFactory->shared(self::SHARED_KEY, basename($path));

        return Inspector::addSegment(function (Segment $segment) use ($path, $data) {
            $segment->addContext('info', ['path' => $path])
                ->addContext('data', $data);

            return $this->engine->get($path, $data);
        }, 'view', $label);
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->engine, $name], $arguments);
    }
}
