<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Models\QuizTopic;
use Osoobe\Quiz\Services\QuizAccess;

class QuizTopicPolicy
{
    public function __construct(private QuizAccess $access) {}

    public function viewAny(?QuizUser $user): bool
    {
        return true;
    }

    public function view(?QuizUser $user, QuizTopic $topic): bool
    {
        return $topic->is_active || ($user && $this->access->isStaff($user));
    }

    public function create(QuizUser $user): bool
    {
        return $this->access->isStaff($user);
    }

    public function update(QuizUser $user, QuizTopic $topic): bool
    {
        return $this->access->isStaff($user);
    }

    public function delete(QuizUser $user, QuizTopic $topic): bool
    {
        return $this->access->isStaff($user);
    }
}
