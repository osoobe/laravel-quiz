<?php

namespace Osoobe\Quiz\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface QuizAuthorizer
{
    /**
     * Full manage access + attempt-limit bypass.
     */
    public function isStaff(Authenticatable $user): bool;

    /**
     * May manage private-quiz invitations (in addition to the quiz creator).
     */
    public function isInvitationManager(Authenticatable $user): bool;

    /**
     * Escape hatch for host-specific abilities not covered above.
     */
    public function can(Authenticatable $user, string $ability): bool;
}
