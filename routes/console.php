<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:serve {--host=127.0.0.1} {--port=8000} {--queue=tests,default} {--tries=1} {--timeout=600} {--sleep=2} {--memory=512} {--no-worker : Run only the HTTP development server}', function () {
    $host = (string) $this->option('host');
    $port = (string) $this->option('port');
    $queue = (string) $this->option('queue');
    $tries = (string) $this->option('tries');
    $timeout = (string) $this->option('timeout');
    $sleep = (string) $this->option('sleep');
    $memory = (string) $this->option('memory');
    $php = PHP_BINARY;
    $basePath = base_path();

    $this->info("Starting 21-LMS development stack on http://{$host}:{$port}");

    $processes = [];
    $processes['server'] = new Process([
        $php,
        'artisan',
        'serve',
        '--host=' . $host,
        '--port=' . $port,
    ], $basePath, null, null, null);

    if (! $this->option('no-worker')) {
        $processes['tests-worker'] = new Process([
            $php,
            'artisan',
            'queue:work',
            '--queue=' . $queue,
            '--tries=' . $tries,
            '--timeout=' . $timeout,
            '--sleep=' . $sleep,
            '--memory=' . $memory,
        ], $basePath, null, null, null);
    }

    $running = true;
    $stopAll = function () use (&$processes, &$running): void {
        $running = false;
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop(10, SIGTERM);
            }
        }
    };

    if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, $stopAll);
        pcntl_signal(SIGTERM, $stopAll);
    }

    foreach ($processes as $name => $process) {
        $process->start(function (string $type, string $buffer) use ($name): void {
            $prefix = '[' . $name . '] ';
            foreach (preg_split('/\r?\n/', rtrim($buffer, "\r\n")) as $line) {
                if ($line !== '') {
                    $this->line($prefix . $line);
                }
            }
        });
    }

    $this->comment('Press Ctrl+C to stop server and workers.');

    while ($running) {
        foreach ($processes as $name => $process) {
            if (! $process->isRunning()) {
                $exitCode = $process->getExitCode();
                $this->warn("Process {$name} stopped with exit code {$exitCode}. Stopping stack...");
                $stopAll();
                break 2;
            }
        }

        usleep(200000);
    }

    return 0;
})->purpose('Run the local 21-LMS HTTP server and tests queue worker together');
