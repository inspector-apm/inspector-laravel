<?php

declare(strict_types=1);

namespace Inspector\Laravel;

use function error_get_last;
use function ini_set;
use function preg_match;
use function register_shutdown_function;
use function str_repeat;

class OutOfMemoryBootstrapper
{
    /**
     * A bit of reserved memory to ensure we are able to increase the memory
     * limit on an OOM.
     *
     * We can't reserve all the memory that we need to send OOM reports
     * because this would have a big overhead on every request, instead of just
     * on shutdown in requests with errors.
     */
    protected ?string $reservedMemory = null;

    /**
     * A regex that matches PHP OOM errors.
     */
    protected string $oomRegex = '/^Allowed memory size of (\d+) bytes exhausted \(tried to allocate \d+ bytes\)/';

    /**
     * Allow Bugsnag to handle OOMs by registering a shutdown function that
     * increases the memory limit. This must happen before Laravel's shutdown
     * function is registered or it will have no effect.
     */
    public function bootstrap(): void
    {
        $this->reservedMemory = str_repeat(' ', 1024 * 256);

        register_shutdown_function(function (): void {
            $this->reservedMemory = null;

            $lastError = error_get_last();

            if (!$lastError) {
                return;
            }

            $isOom = preg_match($this->oomRegex, $lastError['message'], $matches) === 1;

            if (!$isOom) {
                return;
            }

            // If inspector is recording bump the memory limit so we can report it.
            // The service can be missing when the container isn't complete,
            // e.g. when unit tests are running
            if (inspector()->isRecording()) {
                $currentMemoryLimit = (int) $matches[1];

                ini_set('memory_limit', $currentMemoryLimit + (5 * 1024 * 1024)); // 5MB
            }
        });
    }
}
