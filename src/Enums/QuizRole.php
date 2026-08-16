<?php

namespace Osoobe\Quiz\Enums;

/**
 * Canonical Spatie role names for quiz access. These are plain role-name strings
 * matched via `hasAnyRole()` by `SpatieQuizAuthorizer` — see `config('quiz.staff_roles')`
 * / `config('quiz.invitation_manager_roles')`. There is no `staff` column; a user's
 * standing is whatever roles the host app has assigned them.
 */
enum QuizRole: string
{
    case Owner = 'quiz_owner';
    case Admin = 'quiz_admin';
    case Moderator = 'quiz_moderator';
    case Taker = 'quiz_taker';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Quiz Owner',
            self::Admin => 'Quiz Admin',
            self::Moderator => 'Quiz Moderator',
            self::Taker => 'Quiz Taker',
        };
    }

    /** Matches the default `config('quiz.staff_roles')` set — full manage access + attempt-limit bypass. */
    public function isStaff(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Moderator => true,
            self::Taker => false,
        };
    }

    /** Matches the default `config('quiz.invitation_manager_roles')` set. */
    public function isInvitationManager(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Moderator, self::Taker => false,
        };
    }

    /** @return array<int, self> */
    public static function staffRoles(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => $role->isStaff()));
    }

    /** @return array<int, self> */
    public static function invitationManagerRoles(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => $role->isInvitationManager()));
    }
}
