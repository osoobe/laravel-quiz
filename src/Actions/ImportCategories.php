<?php

namespace Osoobe\Quiz\Actions;

use Osoobe\Quiz\Actions\Concerns\ImportsNamedEntities;
use Osoobe\Quiz\Models\QuizCategory;

class ImportCategories
{
    use ImportsNamedEntities;

    protected function model(): string
    {
        return QuizCategory::class;
    }
}
