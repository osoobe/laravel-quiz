<?php

namespace Osoobe\Quiz\Enums;

enum QuizAudience: string
{
    case Everyone = 'everyone';
    case LoggedIn = 'logged_in';
    case Private = 'private';

    public static function isScoped(string $audience): bool
    {
        return str_starts_with($audience, config('quiz.scoped_prefix', 'scope-'));
    }
}
