<?php

namespace Inspector\Laravel\Facades;


use Illuminate\Support\Facades\Facade;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;

/**
 * @method static bool isRecording
 * @method static Transaction startTransaction($name)
 * @method static Transaction currentTransaction()
 * @method static Segment startSegment($type, $label)
 * @method static mixed addSegment($callback, $type, $label, $throw = false)
 * @method static Error reportException(\Throwable $exception, $handled = true)
 * @method static void flush()
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
