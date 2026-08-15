<?php

namespace Osoobe\Quiz\Exceptions;

class NoQuestionsAvailableException extends QuizException
{
    protected string $errorCode = 'quiz.no_questions';

    public function __construct()
    {
        parent::__construct('This quiz has no available questions.');
    }
}
