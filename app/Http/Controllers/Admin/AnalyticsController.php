<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDashboard;
use App\Services\DataLensAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AnalyticsController extends Controller
{
    /**
     * @return View|RedirectResponse
     */
    public function index(Request $request, DataLensAiService $dataLensAi)
    {
        $activeJob = null;
        $jobId = $request->query('job');
        $jobContext = $jobId ? $request->session()->get("analytics_jobs.{$jobId}", []) : [];
        $jobOperation = $jobContext['operation'] ?? null;
        $editDashboardId = (int) ($jobContext['dashboard_id'] ?? 0);
        $jobPrompt = (string) ($jobContext['prompt'] ?? '');
        $conversationDashboardId = (int) ($jobContext['conversation_dashboard_id'] ?? 0);
        $userMessageStored = (bool) ($jobContext['user_message_stored'] ?? false);

        if ($jobId) {
            try {
                $activeJob = $dataLensAi->jobStatus($jobId);
            } catch (Throwable $exception) {
                return redirect()
                    ->route('admin.analytics.index')
                    ->with('error', 'Задача AI недоступна: ' . $exception->getMessage());
            }

            if (($activeJob['status'] ?? null) === 'completed') {
                if ($jobOperation === 'generate') {
                    $result = $activeJob['result'] ?? [];
                    $attributes = [
                        'workbook_id' => $result['workbook_id'],
                        'connection_id' => $result['connection_id'] ?? null,
                        'title' => ($result['title'] ?? null) ?: Str::limit($jobPrompt, 100),
                        'prompt' => $jobPrompt,
                        'dashboard_url' => $result['dashboard_url'],
                        'embed_url' => $result['embed_url'],
                        'charts' => $result['charts'] ?? [],
                        'status' => 'ready',
                    ];
                    $dashboard = $conversationDashboardId
                        ? AnalyticsDashboard::query()->where('user_id', $request->user()->id)->find($conversationDashboardId)
                        : null;

                    if ($dashboard && $dashboard->status === 'conversation') {
                        $dashboard->update(array_merge($attributes, [
                            'datalens_dashboard_id' => $result['dashboard_id'],
                        ]));
                    } else {
                        $dashboard = AnalyticsDashboard::firstOrCreate(
                            ['datalens_dashboard_id' => $result['dashboard_id']],
                            array_merge($attributes, ['user_id' => $request->user()->id]),
                        );
                    }

                    $dashboard->messages()->create([
                        'role' => 'assistant',
                        'content' => 'Создал dashboard «' . $dashboard->title . '» с ' . count($result['charts'] ?? []) . ' графиками.',
                    ]);

                    $request->session()->forget('analytics_initial_dashboard_id');
                    $request->session()->forget("analytics_jobs.{$jobId}");

                    return redirect()
                        ->route('admin.analytics.index', ['dashboard' => $dashboard->id])
                        ->with('success', 'AI создал новый DataLens dashboard.');
                }

                if ($jobOperation === 'edit' && $editDashboardId) {
                    $dashboard = AnalyticsDashboard::query()
                        ->where('user_id', $request->user()->id)
                        ->findOrFail($editDashboardId);

                    $dashboard->update([
                        'prompt' => $jobPrompt,
                        'status' => 'ready',
                        'last_error' => null,
                    ]);

                    $result = $activeJob['result'] ?? [];
                    $added = count($result['added'] ?? []);
                    $updated = count($result['updated'] ?? []);
                    $deleted = count($result['deleted'] ?? []);

                    // Dashboards created before chat history existed have no
                    // initial turn. Backfill one concise turn before storing
                    // this edit, so the second request opens the chat panel.
                    if (! $dashboard->messages()->exists() && $dashboard->prompt) {
                        $dashboard->messages()->createMany([
                            ['role' => 'user', 'content' => $dashboard->prompt],
                            ['role' => 'assistant', 'content' => 'Создал dashboard «' . $dashboard->title . '».'],
                        ]);
                    }

                    if (! $userMessageStored) {
                        $dashboard->messages()->create([
                            'role' => 'user',
                            'content' => $jobPrompt,
                        ]);
                    }
                    $dashboard->messages()->create([
                        'role' => 'assistant',
                        'content' => "Обновил dashboard: добавлено {$added}, изменено {$updated}, удалено {$deleted}.",
                    ]);

                    $request->session()->forget("analytics_jobs.{$jobId}");

                    return redirect()
                        ->route('admin.analytics.index', ['dashboard' => $dashboard->id])
                        ->with('success', "Dashboard обновлён: добавлено {$added}, изменено {$updated}, удалено {$deleted}.");
                }
            }

            if (($activeJob['status'] ?? null) === 'failed') {
                $request->session()->forget("analytics_jobs.{$jobId}");
                $error = (string) ($activeJob['error'] ?? 'Неизвестная ошибка.');
                $message = Str::contains($error, 'ENTRY_IS_LOCKED')
                    ? 'Dashboard временно занят DataLens. Попробуйте повторить запрос через несколько секунд.'
                    : (Str::contains($error, 'ENTITY_NOT_FOUND')
                        ? 'Указанный пользователь или проект не найден в аналитической БД. Проверьте email или название.'
                        : (Str::contains($error, 'ENTITY_NO_DATA')
                            ? 'Пользователь или проект найден, но для запрошенной аналитики пока нет данных.'
                            : 'Задача AI завершилась ошибкой. Проверьте логи datalens-ai.'));

                return redirect()
                    ->route('admin.analytics.index', ['dashboard' => $editDashboardId])
                    ->with('error', $message);
            }
        }

        $dashboards = AnalyticsDashboard::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $selectedDashboard = null;
        $resetMode = $request->boolean('reset');
        $selectedId = $request->integer('dashboard');

        if ($resetMode) {
            $conversationId = (int) $request->session()->get('analytics_initial_dashboard_id', 0);
            $selectedDashboard = $conversationId ? $dashboards->firstWhere('id', $conversationId) : null;
        } elseif ($selectedId) {
            $selectedDashboard = $dashboards->firstWhere('id', $selectedId);
        }

        if (! $resetMode) {
            $selectedDashboard ??= $dashboards->first();
        }

        $selectedDashboard?->load('messages');

        return view('admin.analytics.index', [
            'dashboards' => $dashboards,
            'selectedDashboard' => $selectedDashboard,
            'serviceHealth' => $dataLensAi->health(),
            'activeJob' => $activeJob,
            'jobStreamUrl' => $jobId
                ? rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($jobId) . '/events'
                : null,
            'previewUrl' => $selectedDashboard
                ? $dataLensAi->dashboardEmbedUrl(
                    $selectedDashboard->datalens_dashboard_id,
                    $selectedDashboard->title,
                )
                : null,
        ]);
    }

    /** Open an isolated empty conversation without deleting saved dashboards. */
    public function create(Request $request): RedirectResponse
    {
        $request->session()->forget('analytics_initial_dashboard_id');

        return redirect()->route('admin.analytics.index', ['reset' => 1]);
    }

    private function initialConversationDashboard(Request $request): AnalyticsDashboard
    {
        $conversationId = $request->session()->get('analytics_initial_dashboard_id');
        $dashboard = $conversationId
            ? AnalyticsDashboard::query()->where('user_id', $request->user()->id)->find($conversationId)
            : null;

        if (! $dashboard || $dashboard->status !== 'conversation') {
            $dashboard = AnalyticsDashboard::create([
                'user_id' => $request->user()->id,
                'datalens_dashboard_id' => 'conversation-' . Str::uuid(),
                'workbook_id' => 'conversation',
                'title' => 'Новый чат',
                'prompt' => '',
                'dashboard_url' => '#',
                'embed_url' => '#',
                'charts' => [],
                'status' => 'conversation',
            ]);
            $request->session()->put('analytics_initial_dashboard_id', $dashboard->id);
        }

        return $dashboard;
    }

    /** @return View */
    public function dashboards(Request $request): View
    {
        $dashboards = AnalyticsDashboard::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('admin.analytics.dashboards', compact('dashboards'));
    }

    public function generate(Request $request, DataLensAiService $dataLensAi): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $job = $dataLensAi->startGeneration($data['message']);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Не удалось запустить AI-задачу: ' . $exception->getMessage());
        }

        if (empty($job['job_id'])) {
            return back()->withInput()->with('error', 'AI service не вернул ID задачи.');
        }

        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'generate',
            'prompt' => $data['message'],
        ]);

        return redirect()->route('admin.analytics.index', [
            'job' => $job['job_id'],
        ]);
    }

    public function edit(Request $request, AnalyticsDashboard $analyticsDashboard, DataLensAiService $dataLensAi): RedirectResponse
    {
        abort_unless($analyticsDashboard->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $job = $dataLensAi->startEdit(
                $analyticsDashboard->datalens_dashboard_id,
                $data['message'],
                $analyticsDashboard->connection_id,
            );
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Не удалось запустить изменение dashboard: ' . $exception->getMessage());
        }

        if (empty($job['job_id'])) {
            throw new RuntimeException('AI service did not return a job ID.');
        }

        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'edit',
            'dashboard_id' => $analyticsDashboard->id,
            'prompt' => $data['message'],
        ]);

        return redirect()->route('admin.analytics.index', [
            'dashboard' => $analyticsDashboard->id,
            'job' => $job['job_id'],
        ]);
    }

    /** @return View|RedirectResponse */
    public function agent(Request $request, DataLensAiService $dataLensAi)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $conversationDashboard = $this->initialConversationDashboard($request);
        $conversationDashboard->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);
        if (! $conversationDashboard->prompt) {
            $conversationDashboard->update(['prompt' => $data['message']]);
        }

        $agent = $dataLensAi->agentRespond($data['message']);
        if (($agent['tool'] ?? 'chat') === 'chat') {
            $reply = (string) ($agent['reply'] ?? 'Я на связи. Чем помочь?');
            $conversationDashboard->messages()->create([
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return view('admin.analytics.chat-response', [
                'userMessage' => $data['message'],
                'assistantMessage' => $reply,
                'initialConversation' => true,
                'completeUrl' => route('admin.analytics.index', ['reset' => 1]),
            ]);
        }

        $job = $dataLensAi->startGeneration($data['message']);
        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'generate',
            'prompt' => $data['message'],
            'conversation_dashboard_id' => $conversationDashboard->id,
        ]);

        return view('admin.analytics.job-start', [
            'jobId' => $job['job_id'],
            'streamUrl' => rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($job['job_id']) . '/events',
            'completeUrl' => route('admin.analytics.index', ['job' => $job['job_id']]),
        ]);
    }

    /** @return View|RedirectResponse */
    public function chat(Request $request, AnalyticsDashboard $analyticsDashboard, DataLensAiService $dataLensAi)
    {
        abort_unless($analyticsDashboard->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $analyticsDashboard->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $chartContext = collect($analyticsDashboard->charts ?? [])
            ->take(12)
            ->map(fn (array $chart) => ($chart['title'] ?? 'Без названия') . ' [' . ($chart['kind'] ?? 'chart') . ']')
            ->implode('; ');
        $context = "Название: {$analyticsDashboard->title}. Графиков: " . count($analyticsDashboard->charts ?? []) . ". Charts: {$chartContext}";
        $agent = $dataLensAi->agentRespond($data['message'], $context, true);

        if (in_array(($agent['tool'] ?? 'chat'), ['chat', 'inspect_dashboard'], true)) {
            $analyticsDashboard->messages()->create([
                'role' => 'assistant',
                'content' => (string) ($agent['reply'] ?? 'Я на связи.'),
            ]);

            return view('admin.analytics.chat-response', [
                'userMessage' => $data['message'],
                'assistantMessage' => (string) ($agent['reply'] ?? 'Я на связи.'),
                'initialConversation' => false,
                'completeUrl' => null,
            ]);
        }

        $agentTool = $agent['tool'] ?? null;
        $editInstruction = match ($agentTool) {
            'clear_dashboard' => '__CLEAR_DASHBOARD__',
            'replace_dashboard' => '__REPLACE_DASHBOARD__:' . $data['message'],
            default => $data['message'],
        };
        $job = $dataLensAi->startEdit(
            $analyticsDashboard->datalens_dashboard_id,
            $editInstruction,
            $analyticsDashboard->connection_id,
        );
        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'edit',
            'dashboard_id' => $analyticsDashboard->id,
            'prompt' => $data['message'],
            'user_message_stored' => true,
        ]);

        return view('admin.analytics.job-start', [
            'jobId' => $job['job_id'],
            'streamUrl' => rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($job['job_id']) . '/events',
            'completeUrl' => route('admin.analytics.index', [
                'dashboard' => $analyticsDashboard->id,
                'job' => $job['job_id'],
            ]),
        ]);
    }

    public function startLiveGenerate(Request $request, DataLensAiService $dataLensAi): View
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $job = $dataLensAi->startGeneration($data['message']);
        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'generate',
            'prompt' => $data['message'],
        ]);

        return view('admin.analytics.job-start', [
            'jobId' => $job['job_id'],
            'streamUrl' => rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($job['job_id']) . '/events',
            'completeUrl' => route('admin.analytics.index', ['job' => $job['job_id']]),
        ]);
    }

    public function startLiveEdit(Request $request, AnalyticsDashboard $analyticsDashboard, DataLensAiService $dataLensAi): View
    {
        abort_unless($analyticsDashboard->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $job = $dataLensAi->startEdit(
            $analyticsDashboard->datalens_dashboard_id,
            $data['message'],
            $analyticsDashboard->connection_id,
        );
        $request->session()->put("analytics_jobs.{$job['job_id']}", [
            'operation' => 'edit',
            'dashboard_id' => $analyticsDashboard->id,
            'prompt' => $data['message'],
        ]);

        return view('admin.analytics.job-start', [
            'jobId' => $job['job_id'],
            'streamUrl' => rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($job['job_id']) . '/events',
            'completeUrl' => route('admin.analytics.index', [
                'dashboard' => $analyticsDashboard->id,
                'job' => $job['job_id'],
            ]),
        ]);
    }
}
