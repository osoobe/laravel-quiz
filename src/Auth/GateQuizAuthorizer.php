<?php

namespace Osoobe\Quiz\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Contracts\QuizAuthorizer;

/**
 * Delegates to host-defined Gates. Undefined gates fail closed (Gate::allows()
 * returns false rather than throwing), so this driver is safe-by-default even
 * before the host defines `quiz.staff` / `quiz.manage-invitations`.
 */
class GateQuizAuthorizer implements QuizAuthorizer
{
    public function isStaff(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('quiz.staff');
    }

    public function isInvitationManager(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('quiz.manage-invitations');
    }

    public function can(Authenticatable $user, string $ability): bool
    {
        return Gate::forUser($user)->allows($ability);
    }
}
