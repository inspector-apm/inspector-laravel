<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;

use function array_key_exists;
use function is_string;

class CommandServiceProvider extends ServiceProvider
{
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
            if (!$this->shouldBeMonitored($event->command)) {
                return;
            }

            if (Inspector::needTransaction()) {
                Inspector::startTransaction($event->command)
                    ->setType('command')
                    ->addContext('Command', [
                        'arguments' => $event->input->getArguments(),
                        'options' => $event->input->getOptions(),
                    ]);
            } elseif (Inspector::canAddSegments()) {
                $this->segments[$event->command] = Inspector::startSegment('cli.command', $event->command);
            }
        });

        $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event): void {
            if (!$this->shouldBeMonitored($event->command)) {
                return;
            }

            if (Inspector::hasTransaction() && Inspector::transaction()->name === $event->command) {
                Inspector::transaction()->setResult($event->exitCode === 0 ? 'success' : 'error');
            } elseif (array_key_exists($event->command, $this->segments)) {
                $this->segments[$event->command]->end()->addContext('Command', [
                    'exit_code' => $event->exitCode,
                    'arguments' => $event->input->getArguments(),
                    'options' => $event->input->getOptions(),
                ]);
            }
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }

    /**
     * Determine if the current command should be monitored.
     */
    protected function shouldBeMonitored(?string $command): bool
    {
        if (is_string($command)) {
            return Filters::isApprovedArtisanCommand($command, config('inspector.ignore_commands'));
        }

        return false;
    }
}
