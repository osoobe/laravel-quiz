<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Osoobe\Quiz\Models\Quiz;

/**
 * For a host model implementing Osoobe\Quiz\Contracts\QuizScope.
 */
trait HasQuiz
{
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function scopedQuizAudience(): string
    {
        return config('quiz.scoped_prefix', 'scope-').$this->quizScopeIdentifier();
    }
}
