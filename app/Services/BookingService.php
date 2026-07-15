<?php

namespace App\Services;

use App\Models\CalendarSlot;
use App\Models\SubmissionReviewQueue;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Пользователь сдал проект на review.
     *
     * Алгоритм «кто первый сдал — тот и получил»:
     *  1. Ищем свободный слот (самый ранний по дате+времени), НЕ принадлежащий
     *     самому пользователю (нельзя ревьюить самого себя).
     *  2. Есть слот  -> бронируем СИСТЕМОЙ за этим пользователем.
     *  3. Нет слота  -> ставим в конец очереди (FIFO по position).
     *
     * Всё в транзакции с lockForUpdate — два пользователя, сдавшие проект
     * одновременно, не получат один и тот же слот.
     */
    public function assignSlot(User $user, ?int $projectId = null, ?int $submissionId = null): SubmissionReviewQueue
    {
        return DB::transaction(function () use ($user, $projectId, $submissionId) {
            $slot = CalendarSlot::where('status', 'available')
                // FIX (БАГ-3): нельзя получить на review свой собственный слот
                ->where('user_id', '!=', $user->id)
                ->where(function ($q) use ($projectId) {
                    $q->whereNull('project_id')
                        ->orWhere('project_id', $projectId);
                })
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->lockForUpdate()
                ->first();

            if ($slot) {
                $slot->book($user); // book() уже ставит status=booked + booked_by_user_id

                return SubmissionReviewQueue::create([
                    'submission_id' => $submissionId,
                    'user_id'       => $user->id,
                    'slot_id'       => $slot->id,
                    'status'        => 'assigned',
                    'position'      => 0,
                ]);
            }

            // Слотов нет — в конец очереди
            $position = (int) SubmissionReviewQueue::where('status', 'waiting')
                ->lockForUpdate()
                ->max('position');

            return SubmissionReviewQueue::create([
                'submission_id' => $submissionId,
                'user_id'       => $user->id,
                'slot_id'       => null,
                'status'        => 'waiting',
                'position'      => $position + 1,
            ]);
        });
    }

    /**
     * Создан новый слот -> отдать его ПЕРВОМУ в очереди
     * (владелец слота пропускается: свой слот получить нельзя).
     */
    public function processQueue(?int $slotId = null): ?SubmissionReviewQueue
    {
        return DB::transaction(function () use ($slotId) {
            $slot = $slotId
                ? CalendarSlot::where('id', $slotId)->where('status', 'available')->lockForUpdate()->first()
                : CalendarSlot::where('status', 'available')
                    ->orderBy('date', 'asc')
                    ->orderBy('start_time', 'asc')
                    ->lockForUpdate()
                    ->first();

            if (! $slot) {
                return null;
            }

            // Первый в очереди, кому этот слот подходит (не владелец слота)
            $waiting = SubmissionReviewQueue::where('status', 'waiting')
                ->where('user_id', '!=', $slot->user_id)
                ->orderBy('position', 'asc')
                ->lockForUpdate()
                ->first();

            if (! $waiting) {
                return null;
            }

            $slot->book($waiting->user);

            $waiting->update([
                'slot_id' => $slot->id,
                'status'  => 'assigned',
            ]);

            // Сдвигаем позиции оставшихся
            SubmissionReviewQueue::where('status', 'waiting')
                ->where('position', '>', $waiting->position)
                ->decrement('position');

            return $waiting;
        });
    }

    /**
     * Отмена слота владельцем.
     *
     *  - только создатель;
     *  - FIX (БАГ-2): запрещено, если до начала слота МЕНЬШЕ 24 часов
     *    (используется знаковый diff: now()->diffInHours($start, false));
     *  - FIX (БАГ-4): если слот был забронирован системой — назначенный
     *    пользователь возвращается в НАЧАЛО очереди (position 1) и мы сразу
     *    пробуем найти ему другой свободный слот.
     *
     * @return array{success: bool, message: string, assignedUser: ?SubmissionReviewQueue}
     */
    public function cancelSlot(CalendarSlot $slot, User $user): array
    {
        if ($slot->user_id !== $user->id) {
            return ['success' => false, 'message' => 'Unauthorized', 'assignedUser' => null];
        }

        // FIX (БАГ-2): знаковая разница. Для будущего слота положительная.
        $startsAt = Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
        $hoursLeft = now()->diffInHours($startsAt, false);

        if ($hoursLeft < 24) {
            return [
                'success'      => false,
                'message'      => 'Cannot cancel slot less than 24 hours before it starts.',
                'assignedUser' => null,
            ];
        }

        return DB::transaction(function () use ($slot) {
            // Кто был назначен системой на этот слот (если был)
            $displaced = SubmissionReviewQueue::where('slot_id', $slot->id)
                ->where('status', 'assigned')
                ->lockForUpdate()
                ->first();

            $slot->cancel();

            if ($displaced) {
                // FIX (БАГ-4): возвращаем в НАЧАЛО очереди
                SubmissionReviewQueue::where('status', 'waiting')->increment('position');
                $displaced->update([
                    'slot_id'  => null,
                    'status'   => 'waiting',
                    'position' => 1,
                ]);
            }

            // Пытаемся раздать свободные слоты очереди (вернувшийся — первый)
            $assigned = $this->processQueue();

            return [
                'success'      => true,
                'message'      => $assigned
                    ? 'Slot cancelled. Next user in queue has been assigned.'
                    : 'Slot cancelled.',
                'assignedUser' => $assigned,
            ];
        });
    }

    /**
     * Позиция пользователя в очереди (0 — не в очереди).
     */
    public function getQueuePosition(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) (SubmissionReviewQueue::where('status', 'waiting')
            ->where('user_id', $user->id)
            ->value('position') ?? 0);
    }

    /**
     * Очередь ожидания (для отладки/админки).
     */
    public function getSlotQueue(int $slotId): \Illuminate\Support\Collection
    {
        return SubmissionReviewQueue::where('slot_id', $slotId)
            ->where('status', 'waiting')
            ->orderBy('position', 'asc')
            ->get();
    }
}
