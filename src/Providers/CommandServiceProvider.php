<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $segments = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event) {
            // Ignore commands
            if (!$this->shouldBeMonitored()) {
                return;
            }

            if (Inspector::needTransaction()) {
                Inspector::startTransaction($event->command)
                    ->addContext('Command', [
                        'arguments' => $event->input->getArguments(),
                        'options' => $event->input->getOptions(),
                    ]);
            } elseif (Inspector::canAddSegments()) {
                $this->segments[$event->command] = Inspector::startSegment('artisan', $event->command);
            }
        });

        $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event) {
            // Ignore commands
            if (!$this->shouldBeMonitored()) {
                return;
            }

            if(Inspector::currentTransaction()->name === $event->command) {
                Inspector::currentTransaction()->setResult($event->exitCode === 0 ? 'success' : 'error');
            } elseif(array_key_exists($event->command, $this->segments)) {
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
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Determine if the current command should be monitored.
     *
     * @return bool
     */
    protected function shouldBeMonitored(): bool
    {
        return Filters::isApprovedArtisanCommand(config('inspector.ignore_commands')) && Inspector::isRecording();
    }
}
