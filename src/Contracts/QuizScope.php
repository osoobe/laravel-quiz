<?php

namespace Osoobe\Quiz\Contracts;

/**
 * Implemented by a host model (Event, Course, Cohort, ...) that owns a scoped
 * quiz, e.g. audience = "scope-{quizScopeIdentifier()}".
 */
interface QuizScope
{
    public function quizScopeIdentifier(): string;
}
