<?php

namespace Osoobe\Quiz\Actions;

use Osoobe\Quiz\Actions\Concerns\ExportsNamedEntities;
use Osoobe\Quiz\Models\QuizCategory;

class ExportCategories
{
    use ExportsNamedEntities;

    protected function model(): string
    {
        return QuizCategory::class;
    }
}
