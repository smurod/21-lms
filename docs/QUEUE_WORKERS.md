# 21-LMS queue workers

21-LMS uses Laravel database queues for background work. Autotests are dispatched to the `tests` queue by `RunSubmissionTestsJob`.

## Local all-in-one development command

For local development you can run the HTTP server and the tests queue worker with one Artisan command:

```bash
php artisan lms:serve
```

Default behaviour:

```text
HTTP server: http://127.0.0.1:8000
Queue worker: tests,default
Timeout: 600s
Memory: 512MB
```

Options:

```bash
php artisan lms:serve --host=127.0.0.1 --port=8000 --queue=tests,default --tries=1 --timeout=600 --sleep=2 --memory=512
```

Run only the HTTP server:

```bash
php artisan lms:serve --no-worker
```

Important: `php artisan serve` itself remains Laravel's standard command. Use `php artisan lms:serve` when you want the worker to start together with the local server.

## Required worker for autotests

Run this from the project root when you need only the worker:

```bash
bash scripts/workers/tests-worker.sh
```

The script executes:

```bash
php artisan queue:work --queue=tests,default --tries=1 --timeout=600 --sleep=2 --memory=512
```

## Environment variables

The script can be configured without editing it:

```bash
PROJECT_DIR=/path/to/21-lms \
PHP_BIN=/usr/bin/php \
QUEUE_NAMES=tests,default \
QUEUE_TRIES=1 \
QUEUE_TIMEOUT=600 \
QUEUE_SLEEP=2 \
QUEUE_MEMORY=512 \
bash scripts/workers/tests-worker.sh
```

## Hosting panel / process manager command

If the hosting panel has a worker/process command field, use:

```bash
cd /home/smurod_8880/projects/21-lms && bash scripts/workers/tests-worker.sh
```

Or directly:

```bash
cd /home/smurod_8880/projects/21-lms && php artisan queue:work --queue=tests,default --tries=1 --timeout=600 --sleep=2 --memory=512
```

## Supervisor

A ready Supervisor config is provided:

```text
supervisor/21-lms-tests-worker.conf
```

Install on a server with Supervisor:

```bash
sudo cp supervisor/21-lms-tests-worker.conf /etc/supervisor/conf.d/21-lms-tests-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status 21-lms-tests-worker:*
```

Restart after deployment:

```bash
sudo supervisorctl restart 21-lms-tests-worker:*
```

Logs:

```bash
tail -f storage/logs/tests-worker.log
```

## Docker autotest runner

If Docker sandbox is enabled, the worker user must be allowed to run Docker.

Required `.env` example:

```env
TEST_RUNNER_DOCKER_ENABLED=true
TEST_RUNNER_DOCKER_IMAGE=ubuntu:24.04
TEST_RUNNER_DOCKER_CPUS=1
TEST_RUNNER_DOCKER_MEMORY=512m
TEST_RUNNER_DOCKER_NETWORK=none
TEST_RUNNER_DOCKER_PIDS_LIMIT=256
TEST_RUNNER_JOB_TIMEOUT=600
TEST_RUNNER_KEEP_WORKDIR=false
TEST_RUNNER_MAX_REPO_MB=50
TEST_RUNNER_MAX_FILES=2000
```

Check Docker access for the worker user:

```bash
docker run --rm ubuntu:24.04 bash -lc 'echo docker-ok'
```

## Queue health checks

Pending/failed jobs:

```bash
php artisan tinker --execute="dump(['jobs'=>DB::table('jobs')->count(),'failed_jobs'=>DB::table('failed_jobs')->count()]);"
```

Process only currently queued jobs and exit:

```bash
php artisan queue:work --queue=tests,default --stop-when-empty --tries=1 --timeout=600
```

## Production notes

- Run at least one persistent worker for `tests,default`.
- Use one worker first; increase concurrency only after Docker CPU/RAM limits are tested.
- Restart workers after deployment so new PHP code is loaded.
- Keep `QUEUE_CONNECTION=database` or configure another durable queue backend.
