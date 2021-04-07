<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (!Inspector::isRecording()) {
            Inspector::startTransaction(implode(' ', $_SERVER['argv']));
        }

        $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event) {
            if(Inspector::isRecording()) {
                Inspector::currentTransaction()
                    ->addContext('Command', [
                        'exit_code' => $event->exitCode,
                        'arguments' => $event->input->getArguments(),
                        'options' => $event->input->getOptions(),
                    ])->setResult($event->exitCode === 0 ? 'success' : 'error');
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
}
