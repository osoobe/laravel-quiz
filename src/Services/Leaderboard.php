<?php

namespace Osoobe\Quiz\Services;

use Illuminate\Support\Collection;
use Osoobe\Quiz\Models\Quiz;

class Leaderboard
{
    public function forQuiz(Quiz $quiz): Collection
    {
        $limit = (int) config('quiz.leaderboard.limit', 50);

        $query = $quiz->attempts()->completed()->with('user')
            ->orderByDesc('score')
            ->orderBy('completed_at');

        if (config('quiz.leaderboard.best_per_user', false)) {
            return $query->get()->unique('user_id')->values()->take($limit);
        }

        return $query->limit($limit)->get();
    }
}
