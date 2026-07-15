<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;

class XpService
{
    public function add(
        int $userId,
        int $amount,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $description = null
    ): void {
        DB::transaction(function () use ($userId, $amount, $reason, $sourceType, $sourceId, $description) {
            $user = User::findOrFail($userId);
            $user->increment('total_xp', $amount);
            $balanceAfter = $user->total_xp;

            $newLevel = $this->calculateLevel($balanceAfter);
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            XpTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'balance_after' => $balanceAfter,
            ]);
        });
    }

    public function calculateLevel(int $xp): int
    {
        $levels = config('xp.levels');
        ksort($levels);
        $current = 1;
        foreach ($levels as $level => $data) {
            if ($xp >= $data['xp_required']) {
                $current = $level;
            } else {
                break;
            }
        }
        return $current;
    }
}
