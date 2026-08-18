<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Facades\DB;
use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Exceptions\NoQuestionsAvailableException;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Exceptions\QuizInactiveException;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Services\AttemptLimiter;
use Osoobe\Quiz\Services\QuizAccess;

class StartAttempt
{
    public function __construct(
        private QuizAccess $access,
        private AttemptLimiter $limiter,
        private BuildQuestionSet $buildQuestionSet,
    ) {}

    public function execute(QuizUser $user, Quiz $quiz): QuizAttempt
    {
        if (! $this->access->allows($user, $quiz)) {
            // allows() already bypasses this for staff/the quiz creator (checked before
            // is_active — see QuizAccess::allows), so reaching here with an inactive quiz
            // means a regular user hit an inactive quiz, not a staff preview.
            throw $quiz->is_active ? new QuizAccessDeniedException : new QuizInactiveException;
        }

        return DB::transaction(function () use ($user, $quiz) {
            // Resuming an already-open attempt is idempotent — it must not burn another
            // slot against max_attempts on a page refresh or a double-click.
            $existing = $quiz->attempts()
                ->where('user_id', $user->getKey())
                ->where('status', AttemptStatus::InProgress->value)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->limiter->assertMayStart($user, $quiz);

            $questions = $this->buildQuestionSet->execute($quiz);

            if ($questions->isEmpty()) {
                throw new NoQuestionsAvailableException;
            }

            return QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->getKey(),
                'started_at' => now(),
                'total_questions' => $questions->count(),
                'answers' => $questions->map(fn ($question) => [
                    'question_id' => $question->id,
                    'selected_answers' => [],
                    'is_correct' => null,
                ])->all(),
                'status' => AttemptStatus::InProgress->value,
            ]);
        });
    }
}
