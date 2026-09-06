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

        // SSE может не дойти до браузера (reload, закрытая вкладка, переход в
        // другой чат) — тогда результат остаётся в DataLens, а чат зависает
        // без ответа. Поэтому при каждом открытии страницы сверяем все
        // незавершённые job'ы сессии с микросервисом и дооформляем их
        // без участия браузера.
        $reconciled = $this->reconcileSessionJobs($request, $dataLensAi);
        if ($reconciled['dashboard_id'] !== null) {
            return redirect()
                ->route('admin.analytics.index', ['dashboard' => $reconciled['dashboard_id']])
                ->with('success', $reconciled['flash']);
        }
        if ($reconciled['error'] !== null) {
            return redirect()
                ->route('admin.analytics.index', $reconciled['error_dashboard_id'] ? ['dashboard' => $reconciled['error_dashboard_id']] : [])
                ->with('error', $reconciled['error']);
        }
        if ($jobId) {
            $activeJob = $reconciled['statuses'][$jobId] ?? null;
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

    /**
     * Summary of the CURRENT chat's dashboard only — duplicate detection must
     * never look into other chats. An empty conversation (status=conversation)
     * has nothing built yet, so the list stays empty and the agent generates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function knownDashboardsFor(?AnalyticsDashboard $dashboard): array
    {
        if (! $dashboard || $dashboard->status !== 'ready') {
            return [];
        }

        return [[
            'dashboard_id' => $dashboard->datalens_dashboard_id,
            'title' => $dashboard->title,
            'prompt' => Str::limit((string) $dashboard->prompt, 300),
            'charts' => collect($dashboard->charts ?? [])
                ->take(10)
                ->pluck('title')
                ->filter()
                ->values()
                ->all(),
        ]];
    }

    /**
     * Last chat messages — gives the router conversational context, so short
     * answers like "да" resolve against the agent's previous offer.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function historyFor(AnalyticsDashboard $dashboard, bool $excludeLast = false): array
    {
        $messages = $dashboard->messages()
            ->oldest()
            ->get()
            ->map(fn ($message) => [
                'role' => (string) $message->role,
                'content' => Str::limit((string) $message->content, 500),
                // Timestamps let the agent greet only when appropriate
                // (first message or after a day of silence).
                'time' => optional($message->created_at)->format('d.m.Y H:i') ?? '',
            ]);

        if ($excludeLast) {
            $messages = $messages->slice(0, -1);
        }

        return $messages->slice(-10)->values()->all();
    }

    /**
     * SSE может не дойти до браузера (reload, закрытая вкладка, переход в
     * другой чат) — тогда результат job'а остаётся в DataLens, а чат висит
     * без ответа. Сверяем все незавершённые job'ы сессии с микросервисом и
     * дооформляем результаты: сохраняем дашборд, пишем ответ агента, чистим
     * сессию. Работает при каждом открытии страницы.
     *
     * @return array{dashboard_id: ?int, error: ?string, error_dashboard_id: int, statuses: array<string, array<string, mixed>>, flash: string}
     */
    private function reconcileSessionJobs(Request $request, DataLensAiService $dataLensAi): array
    {
        $jobs = $request->session()->get('analytics_jobs', []);
        $result = [
            'dashboard_id' => null,
            'error' => null,
            'error_dashboard_id' => 0,
            'statuses' => [],
            'flash' => '',
        ];
        if (! is_array($jobs) || $jobs === []) {
            return $result;
        }

        foreach ($jobs as $jobId => $context) {
            $jobId = (string) $jobId;
            try {
                $job = $dataLensAi->jobStatus($jobId);
            } catch (Throwable $exception) {
                // Job больше не существует (микросервис перезапускался) —
                // чистим сессию, иначе она будет вечно его сверять.
                if (Str::contains($exception->getMessage(), ['Not Found', '404'])) {
                    $request->session()->forget("analytics_jobs.{$jobId}");
                }
                continue; // сервис недоступен — сверимся при следующем открытии
            }
            $result['statuses'][$jobId] = $job;

            $status = $job['status'] ?? null;
            if (in_array($status, ['queued', 'running'], true)) {
                continue;
            }

            $context = is_array($context) ? $context : [];
            if ($status === 'completed') {
                $outcome = ($context['operation'] ?? 'generate') === 'edit'
                    ? $this->persistEditResult($request, $context, $job['result'] ?? [])
                    : $this->persistGenerationResult($request, $context, $job['result'] ?? []);
                $request->session()->forget("analytics_jobs.{$jobId}");
                if ($outcome !== null) {
                    $result['dashboard_id'] = $outcome['dashboard']->id;
                    $result['flash'] = $outcome['flash'];
                }
            } elseif ($status === 'failed') {
                $result['error'] = $this->jobFailureMessage((string) ($job['error'] ?? ''));
                $result['error_dashboard_id'] = (int) ($context['dashboard_id'] ?? 0);
                $request->session()->forget("analytics_jobs.{$jobId}");
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $result
     * @return array{dashboard: AnalyticsDashboard, flash: string}|null
     */
    private function persistGenerationResult(Request $request, array $context, array $result): ?array
    {
        $jobPrompt = (string) ($context['prompt'] ?? '');
        $conversationDashboardId = (int) ($context['conversation_dashboard_id'] ?? 0);

        $attributes = [
            'workbook_id' => $result['workbook_id'] ?? '',
            'connection_id' => $result['connection_id'] ?? null,
            'title' => ($result['title'] ?? null) ?: Str::limit($jobPrompt, 100),
            'prompt' => $jobPrompt,
            'dashboard_url' => $result['dashboard_url'] ?? '#',
            'embed_url' => $result['embed_url'] ?? '#',
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

        $assistantReply = trim((string) ($result['reply'] ?? ''))
            ?: 'Создал dashboard «' . $dashboard->title . '» с ' . count($result['charts'] ?? []) . ' графиками.';

        // The streamed confirmation message (if any) is UPDATED with the job
        // result — the tool-call turn stays a single chat bubble.
        $replyMessageId = (int) ($context['reply_message_id'] ?? 0);
        $replyMessage = $replyMessageId
            ? $dashboard->messages()->where('id', $replyMessageId)->where('role', 'assistant')->first()
            : null;

        if ($replyMessage) {
            $replyMessage->update(['content' => $assistantReply]);
        } else {
            $dashboard->messages()->create([
                'role' => 'assistant',
                'content' => $assistantReply,
            ]);
        }

        $request->session()->forget('analytics_initial_dashboard_id');

        return ['dashboard' => $dashboard, 'flash' => 'AI создал новый DataLens dashboard.'];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $result
     * @return array{dashboard: AnalyticsDashboard, flash: string}|null
     */
    private function persistEditResult(Request $request, array $context, array $result): ?array
    {
        $editDashboardId = (int) ($context['dashboard_id'] ?? 0);
        $jobPrompt = (string) ($context['prompt'] ?? '');
        $userMessageStored = (bool) ($context['user_message_stored'] ?? false);

        $dashboard = AnalyticsDashboard::query()
            ->where('user_id', $request->user()->id)
            ->find($editDashboardId);
        if (! $dashboard) {
            return null;
        }

        $attributes = [
            'prompt' => $jobPrompt,
            'status' => 'ready',
            'last_error' => null,
        ];
        // The AI reply and the chat context must describe the real
        // dashboard state, so persist the fresh chart list.
        if (! empty($result['charts']) && is_array($result['charts'])) {
            $attributes['charts'] = $result['charts'];
        }
        $dashboard->update($attributes);

        $added = count($result['added'] ?? []);
        $updated = count($result['updated'] ?? []);
        $deleted = count($result['deleted'] ?? []);

        // Dashboards created before chat history existed have no initial turn.
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
        $assistantReply = trim((string) ($result['reply'] ?? ''))
            ?: "Обновил dashboard: добавлено {$added}, изменено {$updated}, удалено {$deleted}.";

        // Update the streamed confirmation instead of adding a second bubble.
        $replyMessageId = (int) ($context['reply_message_id'] ?? 0);
        $replyMessage = $replyMessageId
            ? $dashboard->messages()->where('id', $replyMessageId)->where('role', 'assistant')->first()
            : null;

        if ($replyMessage) {
            $replyMessage->update(['content' => $assistantReply]);
        } else {
            $dashboard->messages()->create([
                'role' => 'assistant',
                'content' => $assistantReply,
            ]);
        }

        return ['dashboard' => $dashboard, 'flash' => "Dashboard обновлён: добавлено {$added}, изменено {$updated}, удалено {$deleted}."];
    }

    private function jobFailureMessage(string $error): string
    {
        return Str::contains($error, 'CANCELED')
            ? 'Остановлено пользователем.'
            : (Str::contains($error, 'ENTRY_IS_LOCKED')
                ? 'Dashboard временно занят DataLens. Попробуйте повторить запрос через несколько секунд.'
                : (Str::contains($error, 'ENTITY_NOT_FOUND')
                    ? 'Указанный пользователь или проект не найден в аналитической БД. Проверьте email или название.'
                    : (Str::contains($error, 'ENTITY_NO_DATA')
                        ? 'Пользователь или проект найден, но для запрошенной аналитики пока нет данных.'
                        : 'Задача AI завершилась ошибкой. Проверьте логи datalens-ai.')));
    }

    /**
     * AJAX step 1 of the streaming chat: store the user message and return
     * the exact payload the browser streams to the AI microservice.
     */
    public function chatPrepare(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'dashboard_id' => ['nullable', 'integer'],
        ]);

        $dashboard = $data['dashboard_id']
            ? AnalyticsDashboard::query()->where('user_id', $request->user()->id)->findOrFail($data['dashboard_id'])
            : $this->initialConversationDashboard($request);

        $dashboard->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);
        if (! $dashboard->prompt) {
            $dashboard->update(['prompt' => $data['message']]);
        }

        return response()->json([
            'dashboard_id' => $dashboard->id,
            'is_ready_dashboard' => $dashboard->status === 'ready',
            'payload' => [
                'message' => $data['message'],
                'dashboard_context' => $dashboard->status === 'ready' ? $this->dashboardContextFor($dashboard) : null,
                'has_dashboard' => $dashboard->status === 'ready',
                'known_dashboards' => $this->knownDashboardsFor($dashboard),
                // The just-stored message is the current turn, not history.
                'history' => $this->historyFor($dashboard, excludeLast: true),
            ],
        ]);
    }

    /** AJAX step 3: persist the streamed assistant reply. */
    public function chatStoreReply(Request $request)
    {
        $data = $request->validate([
            'dashboard_id' => ['required', 'integer'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $dashboard = AnalyticsDashboard::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($data['dashboard_id']);
        $message = $dashboard->messages()->create([
            'role' => 'assistant',
            'content' => $data['content'],
        ]);

        // The id lets the job reconciliation UPDATE this message (confirmation
        // → final result) instead of creating a second assistant bubble.
        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }

    /**
     * AJAX step for tool decisions: start the dashboard job the AI agent
     * selected (the routing itself happened inside the AI stream).
     *
     * @return JsonResponse
     */
    public function chatDispatch(Request $request, DataLensAiService $dataLensAi)
    {
        $data = $request->validate([
            'dashboard_id' => ['required', 'integer'],
            'tool' => ['required', 'string', 'in:generate_dashboard,edit_dashboard,clear_dashboard,replace_dashboard'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'message_id' => ['nullable', 'integer'],
        ]);

        $dashboard = AnalyticsDashboard::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($data['dashboard_id']);

        $editInstruction = match ($data['tool']) {
            'clear_dashboard' => '__CLEAR_DASHBOARD__',
            'replace_dashboard' => '__REPLACE_DASHBOARD__:' . $data['message'],
            default => $data['message'],
        };

        if ($data['tool'] === 'generate_dashboard') {
            $job = $dataLensAi->startGeneration($data['message']);
            $context = [
                'operation' => 'generate',
                'prompt' => $data['message'],
                'conversation_dashboard_id' => $dashboard->id,
                // The streamed confirmation message gets UPDATED with the job
                // result, so the tool-call turn stays a single chat bubble.
                'reply_message_id' => $data['message_id'],
            ];
        } else {
            if (! $dashboard->connection_id) {
                return response()->json(['error' => 'У этого dashboard нет подключения к БД.'], 422);
            }
            $job = $dataLensAi->startEdit(
                $dashboard->datalens_dashboard_id,
                $editInstruction,
                $dashboard->connection_id,
            );
            $context = [
                'operation' => 'edit',
                'dashboard_id' => $dashboard->id,
                'prompt' => $data['message'],
                'user_message_stored' => true,
                'reply_message_id' => $data['message_id'],
            ];
        }

        $request->session()->put("analytics_jobs.{$job['job_id']}", $context);

        return response()->json([
            'job_id' => $job['job_id'],
            'stream_url' => rtrim((string) config('datalens_ai.base_url'), '/') . '/api/dashboard-jobs/' . rawurlencode($job['job_id']) . '/events',
            'complete_url' => $data['tool'] === 'generate_dashboard'
                ? route('admin.analytics.index', ['job' => $job['job_id']])
                : route('admin.analytics.index', ['dashboard' => $dashboard->id, 'job' => $job['job_id']]),
        ]);
    }

    private function dashboardContextFor(AnalyticsDashboard $dashboard): string
    {
        $chartContext = collect($dashboard->charts ?? [])
            ->take(12)
            ->map(fn (array $chart) => ($chart['title'] ?? 'Без названия') . ' [' . ($chart['kind'] ?? 'chart') . ']')
            ->implode('; ');

        return "Название: {$dashboard->title}. Графиков: " . count($dashboard->charts ?? []) . ". Charts: {$chartContext}";
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

        try {
            $agent = $dataLensAi->agentRespond(
                $data['message'],
                null,
                false,
                $this->knownDashboardsFor(null),
            );
        } catch (RuntimeException $exception) {
            $reply = 'AI-сервис аналитики сейчас недоступен: ' . $exception->getMessage() . ' Попробуй повторить запрос.';
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
        if (($agent['tool'] ?? 'chat') === 'chat') {
            $reply = trim((string) ($agent['reply'] ?? ''))
                ?: 'AI не вернул ответ. Попробуй переформулировать запрос.';
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
        try {
            $agent = $dataLensAi->agentRespond(
                $data['message'],
                $context,
                true,
                $this->knownDashboardsFor($analyticsDashboard),
                $this->historyFor($analyticsDashboard),
            );
        } catch (RuntimeException $exception) {
            $reply = 'AI-сервис аналитики сейчас недоступен: ' . $exception->getMessage() . ' Попробуй повторить запрос.';
            $analyticsDashboard->messages()->create([
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return view('admin.analytics.chat-response', [
                'userMessage' => $data['message'],
                'assistantMessage' => $reply,
                'initialConversation' => false,
                'completeUrl' => null,
            ]);
        }

        if (in_array(($agent['tool'] ?? 'chat'), ['chat', 'inspect_dashboard'], true)) {
            $reply = trim((string) ($agent['reply'] ?? ''))
                ?: 'AI не вернул ответ. Попробуй переформулировать запрос.';
            $analyticsDashboard->messages()->create([
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return view('admin.analytics.chat-response', [
                'userMessage' => $data['message'],
                'assistantMessage' => $reply,
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
