<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Services\QuizAccess;

class QuizQuestionPolicy
{
    public function __construct(private QuizAccess $access) {}

    // Non-nullable $user: Laravel denies guests before the method even runs (questions
    // require authentication, unlike topics/categories).
    public function viewAny(QuizUser $user): bool
    {
        return true;
    }

    public function view(QuizUser $user, QuizQuestion $question): bool
    {
        return $question->is_active || $this->access->isStaff($user);
    }

    public function create(QuizUser $user): bool
    {
        return $this->access->isStaff($user);
    }

    public function update(QuizUser $user, QuizQuestion $question): bool
    {
        return $this->access->isStaff($user);
    }

    public function delete(QuizUser $user, QuizQuestion $question): bool
    {
        return $this->access->isStaff($user);
    }
}
