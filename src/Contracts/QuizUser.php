<?php

namespace Osoobe\Quiz\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Extends Authenticatable (rather than requiring Eloquent) so QuizAuthorizer's
 * driver methods, which only need Authenticatable, still accept a QuizUser.
 */
interface QuizUser extends Authenticatable
{
    public function getKey();

    public function quizDisplayName(): string;

    public function quizAvatarUrl(): ?string;
}
