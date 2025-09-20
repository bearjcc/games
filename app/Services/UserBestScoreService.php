<?php

namespace App\Services;

use App\Models\UserGameScore;
use Illuminate\Contracts\Auth\Authenticatable;

class UserBestScoreService
{
    public function get(?Authenticatable $user, string $gameSlug): int
    {
        if (!$user) return 0;
        $row = UserGameScore::where('user_id', $user->getAuthIdentifier())
            ->where('game_slug', $gameSlug)
            ->first();
        return $row?->best_score ?? 0;
    }

    public function updateIfBetter(?Authenticatable $user, string $gameSlug, int $score): void
    {
        if (!$user) return;
        $row = UserGameScore::firstOrNew([
            'user_id' => $user->getAuthIdentifier(),
            'game_slug' => $gameSlug,
        ]);
        if (($row->best_score ?? 0) < $score) {
            $row->best_score = $score;
            $row->save();
        }
    }
}


