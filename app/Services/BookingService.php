<?php

namespace App\Services;

use App\Models\Admin\Review;
use App\Models\CalendarSlot;
use App\Models\SubmissionReviewQueue;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Отмена слота/брони.
     *
     *  - владелец слота может отменить сам слот;
     *  - студент, которому слот назначен, может отменить только бронь;
     *  - отмена разрешена не позднее чем за 30 минут до начала.
     *
     * ВАЖНО: автоматическое переназначение очереди отключено. После отмены
     * студент сам выбирает новый свободный слот в календаре.
     *
     * @return array{success: bool, message: string, assignedUser: ?SubmissionReviewQueue}
     */
    public function cancelSlot(CalendarSlot $slot, User $user): array
    {
        $isOwner = $slot->user_id === $user->id;
        $isBookedUser = $slot->booked_by_user_id === $user->id;

        if (! $isOwner && ! $isBookedUser) {
            return ['success' => false, 'message' => 'Unauthorized', 'assignedUser' => null];
        }

        $startsAt = Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
        $minutesLeft = now()->diffInMinutes($startsAt, false);

        if ($minutesLeft < 30) {
            return [
                'success'      => false,
                'message'      => 'Cannot cancel slot less than 30 minutes before it starts.',
                'assignedUser' => null,
            ];
        }

        return DB::transaction(function () use ($slot, $isOwner, $isBookedUser) {
            $displaced = SubmissionReviewQueue::where('slot_id', $slot->id)
                ->where('status', 'assigned')
                ->lockForUpdate()
                ->first();

            $this->deleteActiveReviewForSlot($slot);

            if ($displaced) {
                $displaced->update([
                    'slot_id'  => null,
                    'status'   => 'cancelled',
                    'position' => 0,
                ]);
            }

            if ($isOwner) {
                $slot->cancel();

                return [
                    'success'      => true,
                    'message'      => 'Slot cancelled. Assigned project must choose another free slot manually.',
                    'assignedUser' => null,
                ];
            }

            if ($isBookedUser) {
                $slot->update([
                    'status' => 'available',
                    'booked_by_user_id' => null,
                ]);

                return [
                    'success'      => true,
                    'message'      => 'Booking cancelled. Slot is available again.',
                    'assignedUser' => null,
                ];
            }

            return ['success' => false, 'message' => 'Unable to cancel slot.', 'assignedUser' => null];
        });
    }

    private function deleteActiveReviewForSlot(CalendarSlot $slot): void
    {
        if (! $slot->booked_by_user_id) {
            return;
        }

        Review::where('reviewer_id', $slot->user_id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('submission', fn ($q) => $q->where('user_id', $slot->booked_by_user_id))
            ->delete();
    }


}
