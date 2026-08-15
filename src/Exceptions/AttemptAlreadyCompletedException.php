<?php

namespace Osoobe\Quiz\Exceptions;

class AttemptAlreadyCompletedException extends QuizException
{
    protected string $errorCode = 'quiz.attempt_already_completed';

    public function __construct()
    {
        parent::__construct('This attempt has already been completed.');
    }
}
