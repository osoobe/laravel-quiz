<?php

namespace Osoobe\Quiz\Http\Controllers\Api;

use Osoobe\Quiz\Http\Resources\LeaderboardEntryResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Services\Leaderboard;

class LeaderboardController
{
    public function __construct(private Leaderboard $leaderboard)
    {
    }

    public function show(Quiz $quiz)
    {
        return response()->json([
            'quiz' => ['id' => $quiz->id, 'name' => $quiz->name],
            'entries' => LeaderboardEntryResource::collection($this->leaderboard->forQuiz($quiz)),
        ]);
    }
}
