<?php

namespace Osoobe\Quiz\Exceptions;

class QuizAccessDeniedException extends QuizException
{
    protected string $errorCode = 'quiz.access_denied';

    public function __construct(string $message = 'You do not have access to this quiz.')
    {
        parent::__construct($message);
    }
}
