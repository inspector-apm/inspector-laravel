<?php

declare(strict_types=1);

namespace Inspector\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;

/**
 * @method static Transaction startTransaction($name)
 * @method static Transaction transaction()
 * @method static bool needTransaction()
 * @method static bool hasTransaction()
 * @method static bool canAddSegments()
 * @method static bool isRecording()
 * @method static \Inspector\Inspector startRecording()
 * @method static \Inspector\Inspector stopRecording()
 * @method static Segment startSegment($type, $label)
 * @method static mixed addSegment($callback, $type, $label = null, $throw = false)
 * @method static Error reportException(\Throwable $exception, $handled = true)
 * @method static void flush()
 * @method static void beforeFlush(callable $callback)
 */
class Inspector extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'inspector';
    }
}
