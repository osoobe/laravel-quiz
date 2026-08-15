<?php

namespace Osoobe\Quiz\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Osoobe\Quiz\Contracts\QuizAuthorizer;

class SpatieQuizAuthorizer implements QuizAuthorizer
{
    public function isStaff(Authenticatable $user): bool
    {
        return $user->hasAnyRole(config('quiz.staff_roles', []));
    }

    public function isInvitationManager(Authenticatable $user): bool
    {
        return $user->hasAnyRole(config('quiz.invitation_manager_roles', []));
    }

    public function can(Authenticatable $user, string $ability): bool
    {
        return $user->can($ability);
    }
}
