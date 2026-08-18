<?php

namespace Osoobe\Quiz\Policies;

use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizInvitation;
use Osoobe\Quiz\Services\QuizAccess;

class QuizInvitationPolicy
{
    public function __construct(private QuizAccess $access) {}

    public function viewAny(QuizUser $user, Quiz $quiz): bool
    {
        return $this->access->isInvitationManager($user) || $quiz->created_by === (string) $user->getKey();
    }

    public function create(QuizUser $user, Quiz $quiz): bool
    {
        return $this->viewAny($user, $quiz);
    }

    public function view(QuizUser $user, QuizInvitation $invitation): bool
    {
        return $invitation->user_id === (string) $user->getKey()
            || $this->access->isInvitationManager($user)
            || $invitation->quiz->created_by === (string) $user->getKey();
    }

    public function delete(QuizUser $user, QuizInvitation $invitation): bool
    {
        return $this->access->isInvitationManager($user) || $invitation->quiz->created_by === (string) $user->getKey();
    }
}
