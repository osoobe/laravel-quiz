<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Services\QuizAccess;

class QuizPolicy
{
    public function __construct(private QuizAccess $access) {}

    public function viewAny(?QuizUser $user): bool
    {
        return true;
    }

    public function view(?QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->allows($user, $quiz);
    }

    public function create(QuizUser $user): bool
    {
        return $this->access->isStaff($user);
    }

    public function update(QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->isStaff($user) || $quiz->created_by === (string) $user->getKey();
    }

    public function delete(QuizUser $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    public function manageInvitations(QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->isInvitationManager($user) || $quiz->created_by === (string) $user->getKey();
    }

    public function viewResults(QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->isStaff($user) || $quiz->created_by === (string) $user->getKey();
    }
}
