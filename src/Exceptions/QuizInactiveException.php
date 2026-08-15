<?php

namespace Osoobe\Quiz\Exceptions;

class QuizInactiveException extends QuizException
{
    protected string $errorCode = 'quiz.inactive';

    public function __construct()
    {
        parent::__construct('This quiz is not currently active.');
    }
}
