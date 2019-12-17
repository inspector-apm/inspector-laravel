<?php


namespace Inspector\Laravel\Commands;


use Illuminate\Console\Command;
use Illuminate\Config\Repository;

class InspectorTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inspector:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send data to your Inspector dashboard';

    /**
     * Execute the console command.
     *
     * @param Repository $config
     * @return void
     * @throws \Throwable
     */
    public function handle(Repository $config)
    {
        $this->line("I'm testing your Inspector integration.");

        // Check Inspector API key
        $this->info(!empty($config->get('inspector.key'))
            ? '✅ Inspector key installed.'
            : '❌ Inspector key not specified. Make sure you specify a value in the `key` field of the `inspector` config file.');

        // Check enabled flag
        $this->info($config->get('inspector.enable')
            ? '✅ Inspector is enabled.'
            : '❌ Inspector is actually disabled, turn to true the `enable` field of the `inspector` config file.');

        $this->createSegments();

        $this->reportException();

        $this->line('Done! Explore your data on https://app.inspector.dev/home');
    }

    protected function createSegments()
    {
        foreach (['Segment 1', 'Segment 2'] as $label) {
            inspector()->addSegment(function ($segment) {

                // Code block
                sleep(0.5);
                $segment->addContext('payload', ['foo' => 'bar']);

            }, 'test', $label);

            $this->line($label);
        }
    }

    protected function reportException()
    {
        inspector()->reportException(new \Exception('First Exception detected'));
    }
}
