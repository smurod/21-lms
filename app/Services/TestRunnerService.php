<?php

namespace App\Services;

use App\Models\Admin\ProjectTest;
use App\Models\Admin\Submission;
use App\Models\TestResult;
use App\Models\TestRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class TestRunnerService
{
    /**
     * Run production-like automated tests for a submission.
     *
     * The runner clones the submitted Git repository, checks out the locked
     * commit, enforces repository limits, runs project_tests as hidden/public
     * server-side scripts, writes test_runs/test_results, and updates the
     * submission aggregate score. If a project has no project_tests yet, the
     * legacy runtime command is executed as one test for backward compatibility.
     */
    public function run(Submission $submission): Submission
    {
        $submission->loadMissing(['project.tests', 'user']);
        $project = $submission->project;

        if (! $project || ! $project->has_automated_tests) {
            throw new \RuntimeException('Automated tests are not enabled for this project.');
        }

        if (empty($submission->git_url)) {
            throw new \RuntimeException('Submission git_url is empty. Cannot run automated tests.');
        }

        $submission->update(['status' => 'testing']);

        $runId = now()->format('YmdHis') . '-' . $submission->id . '-' . Str::random(8);
        $baseDir = storage_path('app/test-runs');
        $workDir = $baseDir . '/' . $runId;
        $repoDir = $workDir . '/repo';
        $testsDir = $workDir . '/hidden-tests';
        File::ensureDirectoryExists($baseDir, 0750, true);
        File::ensureDirectoryExists($workDir, 0750, true);
        File::ensureDirectoryExists($testsDir, 0750, true);

        $startedAt = microtime(true);
        $defaultTimeout = max(1, (int) ($project->test_timeout_seconds ?? 120));
        $runnerName = $this->dockerEnabled() ? 'docker_git_project_tests_v1' : 'process_git_project_tests_v1';
        $logs = '';
        $exitCode = 0;
        $earned = 0;
        $possible = 0;
        $results = [];

        $testRun = TestRun::create([
            'submission_id' => $submission->id,
            'status' => 'running',
            'runner' => $runnerName,
            'image' => $this->dockerEnabled() ? env('TEST_RUNNER_DOCKER_IMAGE', 'debian:stable-slim') : null,
            'command' => null,
            'commit_hash' => $submission->git_commit_hash,
            'started_at' => now(),
            'metadata' => [
                'git_url' => $submission->git_url,
                'docker_enabled' => $this->dockerEnabled(),
                'max_repo_mb' => $this->maxRepoMb(),
                'max_files' => $this->maxFiles(),
            ],
        ]);

        try {
            $cloneUrl = $this->cloneUrl($submission);
            $clone = new Process(['git', 'clone', '--depth', '1', $cloneUrl, $repoDir], $workDir, null, null, $defaultTimeout);
            $clone->run();
            $logs .= $this->section('git clone', $this->maskSecrets($clone->getOutput() . $clone->getErrorOutput(), $submission));

            if (! $clone->isSuccessful()) {
                throw new \RuntimeException('Git clone failed with exit code ' . $clone->getExitCode());
            }

            if (! empty($submission->git_commit_hash)) {
                $checkout = new Process(['git', '-c', 'advice.detachedHead=false', 'checkout', '--quiet', $submission->git_commit_hash], $repoDir, null, null, $defaultTimeout);
                $checkout->run();
                $checkoutOutput = trim($this->maskSecrets($checkout->getOutput() . $checkout->getErrorOutput(), $submission));
                $logs .= $this->section(
                    'git checkout ' . $submission->git_commit_hash,
                    $checkoutOutput !== '' ? $checkoutOutput : 'Checked out locked submission commit.'
                );

                if (! $checkout->isSuccessful()) {
                    throw new \RuntimeException('Git checkout failed with exit code ' . $checkout->getExitCode());
                }
            }

            $this->enforceRepositoryLimits($repoDir);

            $tests = $project->tests;
            if ($tests->isEmpty()) {
                $tests = collect([$this->runtimeFallbackTest($project)]);
            }

            foreach ($tests as $test) {
                $points = max(1, (int) $test->points);
                $possible += $points;
                $timeout = max(1, (int) ($test->timeout_seconds ?: $defaultTimeout));
                $scriptPath = $this->writeTestScript($testsDir, $test);
                $commandLabel = $test instanceof ProjectTest
                    ? "{$test->name} ({$test->test_type}, {$points} pts" . ($test->is_hidden ? ', hidden' : ', public') . ')'
                    : $test->name;

                $process = $this->runProjectTest($scriptPath, $repoDir, $testsDir, $timeout);
                $testOutput = $process->getOutput() . $process->getErrorOutput();
                $logs .= $this->section('test: ' . $commandLabel, $testOutput);
                $passed = $process->isSuccessful();
                $testExitCode = $process->getExitCode();

                if ($passed) {
                    $earned += $points;
                } else {
                    $exitCode = $testExitCode ?: 1;
                }

                $results[] = [
                    'test' => $test,
                    'passed' => $passed,
                    'exit_code' => $testExitCode,
                    'error' => $passed ? null : 'Test command failed with exit code ' . $testExitCode,
                    'output' => $testOutput,
                    'duration_ms' => null,
                    'points_earned' => $passed ? $points : 0,
                    'points_possible' => $points,
                ];
            }
        } catch (\Throwable $e) {
            $exitCode = $exitCode ?: 1;
            $logs .= $this->section('runner error', $e->getMessage());

            Log::warning('Automated test run failed', [
                'submission_id' => $submission->id,
                'project_id' => $project->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        $executionMs = (int) round((microtime(true) - $startedAt) * 1000);
        $score = $possible > 0 ? round(($earned / $possible) * 100, 2) : 0.00;
        $passingScore = (float) ($project->passing_score ?? 70);
        $passedRun = $score >= $passingScore && $possible > 0;
        $limitedLogs = Str::limit($logs, 100000, "\n...[truncated]");

        $testRun->update([
            'status' => $passedRun ? 'passed' : 'failed',
            'finished_at' => now(),
            'duration_ms' => $executionMs,
            'exit_code' => $passedRun ? 0 : $exitCode,
            'score' => $score,
            'logs' => $limitedLogs,
            'artifacts_path' => $this->shouldKeepWorkDir() ? $repoDir : null,
            'metadata' => [
                ...($testRun->metadata ?? []),
                'points_earned' => $earned,
                'points_possible' => $possible,
                'tests_count' => count($results),
            ],
        ]);

        if (empty($results) && $possible === 0) {
            $results[] = [
                'test' => null,
                'passed' => false,
                'exit_code' => $exitCode,
                'error' => 'No tests were executed.',
                'output' => $logs,
                'duration_ms' => $executionMs,
                'points_earned' => 0,
                'points_possible' => 1,
            ];
            $possible = 1;
        }

        foreach ($results as $index => $result) {
            $test = $result['test'];
            TestResult::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'test_run_id' => $testRun->id,
                    'test_name' => $test?->name ?? 'project_runtime_command',
                ],
                [
                    'project_test_id' => $test instanceof ProjectTest ? $test->id : null,
                    'passed' => $result['passed'],
                    'error_message' => $result['error'],
                    'output' => Str::limit((string) $result['output'], 60000, "\n...[truncated]"),
                    'execution_time_ms' => $result['duration_ms'] ?? $executionMs,
                    'points_earned' => $result['points_earned'],
                    'points_possible' => $result['points_possible'],
                ]
            );
        }

        $submission->update([
            'tests_total' => $possible,
            'tests_passed' => $earned,
            'test_score' => $score,
            'test_output' => $limitedLogs,
            'test_details' => [
                'test_run_id' => $testRun->id,
                'runner' => $runnerName,
                'work_dir' => $this->shouldKeepWorkDir() ? $repoDir : null,
                'exit_status' => $passedRun ? 0 : $exitCode,
                'points_earned' => $earned,
                'points_possible' => $possible,
                'timestamp' => now()->toIso8601String(),
            ],
            'tested_at' => now(),
            'execution_time_ms' => $executionMs,
            'status' => $passedRun ? 'tested' : 'failed',
        ]);

        if (! $this->shouldKeepWorkDir()) {
            File::deleteDirectory($workDir);
        }

        return $submission->fresh(['project', 'user']);
    }

    public function passes(Submission $submission): bool
    {
        return ((float) ($submission->test_score ?? 0)) >= ((float) ($submission->project->passing_score ?? 70));
    }

    private function runtimeFallbackTest($project): object
    {
        $command = $this->testCommand($project);

        return (object) [
            'id' => null,
            'name' => 'project_runtime_command',
            'description' => 'Fallback runtime command',
            'test_code' => "#!/usr/bin/env bash\nset -euo pipefail\ncd \"$WORKSPACE\"\n{$command}\n",
            'points' => 1,
            'order_position' => 0,
            'is_hidden' => true,
            'test_type' => 'integration',
            'timeout_seconds' => null,
        ];
    }

    private function writeTestScript(string $testsDir, object $test): string
    {
        $safeName = Str::slug($test->name ?: 'test', '-');
        $fileName = str_pad((string) ($test->order_position ?? 0), 3, '0', STR_PAD_LEFT) . '-' . $safeName . '.sh';
        $path = $testsDir . '/' . $fileName;
        $code = (string) $test->test_code;

        if (! str_starts_with($code, '#!')) {
            $code = "#!/usr/bin/env bash\nset -euo pipefail\n" . $code;
        }

        File::put($path, $code);
        chmod($path, 0700);

        return $path;
    }

    private function runProjectTest(string $scriptPath, string $repoDir, string $testsDir, int $timeout): Process
    {
        if ($this->dockerEnabled()) {
            $image = env('TEST_RUNNER_DOCKER_IMAGE', 'debian:stable-slim');
            $dockerScript = '/hidden-tests/' . basename($scriptPath);
            $docker = [
                'docker', 'run', '--rm',
                '--network', env('TEST_RUNNER_DOCKER_NETWORK', 'none'),
                '--cpus', (string) env('TEST_RUNNER_DOCKER_CPUS', '1'),
                '--memory', (string) env('TEST_RUNNER_DOCKER_MEMORY', '512m'),
                '--pids-limit', (string) env('TEST_RUNNER_DOCKER_PIDS_LIMIT', '256'),
                '--read-only',
                '--tmpfs', '/tmp:rw,noexec,nosuid,size=128m',
                '-v', $repoDir . ':/workspace:rw',
                '-v', $testsDir . ':/hidden-tests:ro',
                '-e', 'WORKSPACE=/workspace',
                '-w', '/workspace',
                $image,
                'bash', $dockerScript,
            ];

            $process = new Process($docker, null, null, null, $timeout);
            $process->run();

            return $process;
        }

        $process = new Process(['bash', $scriptPath], null, ['WORKSPACE' => $repoDir], null, $timeout);
        $process->run();

        return $process;
    }

    private function testCommand($project): string
    {
        $runtime = is_array($project->runtime ?? null) ? $project->runtime : [];
        $command = trim((string) ($runtime['command'] ?? ''));

        if ($command !== '') {
            return $command;
        }

        $testFilePath = trim((string) ($project->test_file_path ?? ''));
        if ($testFilePath !== '') {
            return $testFilePath;
        }

        return match (strtolower((string) ($project->language ?? ''))) {
            'c' => 'make test',
            'cpp', 'c++' => 'make test',
            'python' => 'python -m pytest',
            'javascript', 'js', 'typescript', 'ts' => 'npm test',
            'php' => 'composer test',
            'go' => 'go test ./...',
            'rust' => 'cargo test',
            'java' => './gradlew test || mvn test',
            default => 'make test',
        };
    }

    private function enforceRepositoryLimits(string $repoDir): void
    {
        $maxFiles = $this->maxFiles();
        $maxBytes = $this->maxRepoMb() * 1024 * 1024;
        $files = 0;
        $bytes = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($repoDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files++;
                $bytes += $file->getSize();
            }

            if ($files > $maxFiles) {
                throw new \RuntimeException("Repository file limit exceeded: {$files} > {$maxFiles}");
            }

            if ($bytes > $maxBytes) {
                throw new \RuntimeException('Repository size limit exceeded: ' . round($bytes / 1024 / 1024, 2) . "MB > {$this->maxRepoMb()}MB");
            }
        }
    }

    private function cloneUrl(Submission $submission): string
    {
        $url = (string) $submission->git_url;
        $token = $submission->user?->connectedGitlabToken();

        if (! $token || ! str_starts_with($url, 'http')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $auth = 'oauth2:' . rawurlencode($token) . '@';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $parts['scheme'] . '://' . $auth . $parts['host'] . $port . $path . $query;
    }

    private function section(string $title, string $body): string
    {
        return "\n===== {$title} =====\n" . trim($body) . "\n";
    }

    private function maskSecrets(string $output, Submission $submission): string
    {
        $token = $submission->user?->connectedGitlabToken();

        return $token ? str_replace($token, '[masked-token]', $output) : $output;
    }

    private function shouldKeepWorkDir(): bool
    {
        return filter_var(env('TEST_RUNNER_KEEP_WORKDIR', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function dockerEnabled(): bool
    {
        return filter_var(env('TEST_RUNNER_DOCKER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function maxRepoMb(): int
    {
        return max(1, (int) env('TEST_RUNNER_MAX_REPO_MB', 50));
    }

    private function maxFiles(): int
    {
        return max(10, (int) env('TEST_RUNNER_MAX_FILES', 2000));
    }
}
