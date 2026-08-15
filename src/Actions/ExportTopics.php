<?php

namespace Osoobe\Quiz\Actions;

use Osoobe\Quiz\Actions\Concerns\ExportsNamedEntities;
use Osoobe\Quiz\Models\QuizTopic;

class ExportTopics
{
    use ExportsNamedEntities;

    protected function model(): string
    {
        return QuizTopic::class;
    }
}
