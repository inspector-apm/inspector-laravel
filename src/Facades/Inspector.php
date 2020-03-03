<?php

namespace Inspector\Laravel\Facades;


use Illuminate\Support\Facades\Facade;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;

/**
 * @method bool isRecording
 * @method Transaction startTransaction($name)
 * @method Segment startSegment($type, $label)
 * @method mixed addSegment($callback, $type, $label)
 * @method Error reportException(\Throwable $exception, $handled = true)
 */
class Inspector extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return 'inspector';
    }
}
