<?php

namespace Osoobe\Quiz\Exceptions;

class MaxAttemptsReachedException extends QuizException
{
    protected string $errorCode = 'quiz.max_attempts_reached';

    public function __construct(int $maxAttempts)
    {
        parent::__construct("Maximum attempts ({$maxAttempts}) reached for this quiz.");
    }
}
