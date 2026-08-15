<?php

namespace Osoobe\Quiz\Actions;

use Osoobe\Quiz\Actions\Concerns\ImportsNamedEntities;
use Osoobe\Quiz\Models\QuizTopic;

class ImportTopics
{
    use ImportsNamedEntities;

    protected function model(): string
    {
        return QuizTopic::class;
    }
}
