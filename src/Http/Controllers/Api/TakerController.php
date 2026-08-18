<?php

namespace Osoobe\Quiz\Http\Controllers\Api;

use Illuminate\Http\Request;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Exceptions\QuizInactiveException;
use Osoobe\Quiz\Http\Resources\QuizResource;
use Osoobe\Quiz\Http\Resources\TakerAttemptResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Services\QuizAccess;

class TakerController
{
    public function __construct(private QuizAccess $access) {}

    /**
     * Read-only pre-flight: quiz metadata + the caller's current/most recent attempt,
     * so the frontend can render resume/completed/locked states before POSTing to start.
     */
    public function show(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        if (! $this->access->allows($user, $quiz)) {
            throw $quiz->is_active ? new QuizAccessDeniedException : new QuizInactiveException;
        }

        $attempt = $user
            ? $quiz->attempts()->where('user_id', $user->getKey())->latest('started_at')->first()
            : null;

        return response()->json([
            'quiz' => new QuizResource($quiz),
            'attempt' => $attempt ? new TakerAttemptResource($attempt->setRelation('quiz', $quiz)) : null,
            'attempts_used' => $user ? $quiz->attempts()->where('user_id', $user->getKey())->count() : 0,
        ]);
    }
}
