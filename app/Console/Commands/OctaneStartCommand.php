<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class OctaneStartCommand extends Command
{
    protected $signature = 'octane:dev {--workers=4} {--port=8000} {--watch}';

    protected $description = 'Start Octane with development-friendly defaults';

    public function handle(): int
    {
        $workers = $this->option('workers');
        $port = $this->option('port');
        $watch = $this->option('watch');

        $command = [
            'php',
            'artisan',
            'octane:start',
            "--workers={$workers}",
            "--port={$port}",
        ];

        if ($watch) {
            $command[] = '--watch';
        }

        $this->info("Starting Octane with {$workers} workers on port {$port}...");
        if ($watch) {
            $this->info('File watching enabled - server will auto-reload on changes');
        }

        $process = new Process($command);
        $process->setTty(true);
        $process->setTimeout(null);

        return $process->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
