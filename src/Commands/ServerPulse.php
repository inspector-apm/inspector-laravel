<?php


namespace Inspector\Laravel\Commands;


use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

class ServerPulse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inspector:pulse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect server resources consumption.';


    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {
        if (inspector()->hasTransaction() && inspector()->isRecording()) {
            inspector()->currentTransaction()->sampleServerStatus(1);
        }
    }
}
