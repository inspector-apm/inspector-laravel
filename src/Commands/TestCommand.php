<?php

namespace Inspector\Laravel\Commands;


use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
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
    protected $description = 'Send data to your Inspector dashboard.';

    /**
     * Execute the console command.
     *
     * @param Repository $config
     * @return void
     * @throws \Throwable
     */
    public function handle(Repository $config)
    {
        if (!inspector()->isRecording()) {
            $this->warn('Inspector is not enabled');
            return;
        }

        $this->line("I'm testing your Inspector integration.");

        // Test proc_open function availability
        try {
            proc_open("", [], $pipes);
        } catch (\Throwable $exception) {
            $this->warn("❌ proc_open function disabled.");
            return;
        }

        // Check Inspector API key
        inspector()->addSegment(function ($segment) use ($config) {
            usleep(10 * 1000);

            !empty($config->get('inspector.key'))
                ? $this->info('✅ Inspector key installed.')
                : $this->warn('❌ Inspector key not specified. Make sure you specify ' .
                              'the INSPECTOR_INGESTION_KEY in your .env file.');

            $segment->addContext('example payload', ['key' => $config->get('inspector.key')]);
        }, 'test', 'Check Ingestion key');

        // Check Inspector is enabled
        inspector()->addSegment(function ($segment) use ($config) {
            usleep(10 * 1000);

            $config->get('inspector.enable')
                ? $this->info('✅ Inspector is enabled.')
                : $this->warn('❌ Inspector is actually disabled, turn to true the `enable` ' .
                              'field of the `inspector` config file.');

            $segment->addContext('another payload', ['enable' => $config->get('inspector.enable')]);
        }, 'test', 'Check if Inspector is enabled');

        // Check CURL
        inspector()->addSegment(function ($segment) {
            usleep(10 * 1000);

            function_exists('curl_version')
                ? $this->info('✅ CURL extension is enabled.')
                : $this->warn('❌ CURL is actually disabled so your app could not be able to send data to Inspector.');

            $segment->addContext('another payload', ['foo' => 'bar']);
        }, 'test', 'Check CURL extension');

        // Report Exception
        inspector()->reportException(new \Exception('First Exception detected'));
        // End the transaction
        inspector()->transaction()
            ->setResult('success')
            ->end();

        // Logs will be reported in the transaction context.
        Log::debug("Here you'll find log entries generated during the transaction.");

        $this->line('Done!');
    }
}
