<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Services\QuizAccess;

class QuizAttemptPolicy
{
    public function __construct(private QuizAccess $access)
    {
    }

    public function view(QuizUser $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === (string) $user->getKey() || $this->access->isStaff($user);
    }

    public function create(QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->allows($user, $quiz);
    }

    // MadeIn allows owner updates in any status; the package tightens this so a
    // completed attempt can never be rewritten.
    public function update(QuizUser $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === (string) $user->getKey() && $attempt->status === AttemptStatus::InProgress;
    }

    public function delete(QuizUser $user, QuizAttempt $attempt): bool
    {
        return $this->access->isStaff($user) || $attempt->quiz->created_by === (string) $user->getKey();
    }
}
