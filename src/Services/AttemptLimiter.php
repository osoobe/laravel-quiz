<?php

namespace Osoobe\Quiz\Services;

use Osoobe\Quiz\Contracts\QuizAuthorizer;
use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Exceptions\MaxAttemptsReachedException;
use Osoobe\Quiz\Models\Quiz;

class AttemptLimiter
{
    public function __construct(private QuizAuthorizer $authorizer) {}

    /**
     * Must run inside the same transaction as the attempt insert (see Actions\StartAttempt)
     * — lockForUpdate() prevents two concurrent requests from both passing the check.
     */
    public function assertMayStart(QuizUser $user, Quiz $quiz): void
    {
        if ($this->authorizer->isStaff($user)) {
            return;
        }

        $query = $quiz->attempts()->where('user_id', $user->getKey());

        if (! config('quiz.count_incomplete_attempts', true)) {
            $query->where('status', AttemptStatus::Completed->value);
        }

        $used = $query->lockForUpdate()->count();

        if ($used >= $quiz->max_attempts) {
            throw new MaxAttemptsReachedException($quiz->max_attempts);
        }
    }
}
