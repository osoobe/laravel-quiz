<?php

namespace Osoobe\Quiz\Exceptions;

class AttemptExpiredException extends QuizException
{
    protected string $errorCode = 'quiz.attempt_expired';

    public function __construct()
    {
        parent::__construct('This attempt has expired.');
    }
}
