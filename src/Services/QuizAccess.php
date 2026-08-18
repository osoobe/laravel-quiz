<?php

namespace Osoobe\Quiz\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Osoobe\Quiz\Contracts\QuizAuthorizer;
use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Enums\QuizAudience;
use Osoobe\Quiz\Models\Quiz;

class QuizAccess
{
    public function __construct(private QuizAuthorizer $authorizer) {}

    public function isStaff(Authenticatable $user): bool
    {
        return $this->authorizer->isStaff($user);
    }

    public function isInvitationManager(Authenticatable $user): bool
    {
        return $this->authorizer->isInvitationManager($user);
    }

    /**
     * Port of the MadeIn can_access_quiz() SECURITY DEFINER function.
     * Evaluation order matters — see docs/quiz/03-access-control.md §3.2.
     */
    public function allows(?QuizUser $user, Quiz $quiz): bool
    {
        if ($user && $this->isStaff($user)) {
            return true;
        }

        if ($user && $quiz->created_by === (string) $user->getKey()) {
            return true;
        }

        if (! $quiz->is_active) {
            return false;
        }

        if ($quiz->isScoped()) {
            return $user !== null;
        }

        return match ($quiz->audience) {
            QuizAudience::Everyone->value => true,
            QuizAudience::LoggedIn->value => $user !== null,
            QuizAudience::Private->value => $user !== null
                && $quiz->invitations()->where('user_id', $user->getKey())->exists(),
            default => false,
        };
    }
}
