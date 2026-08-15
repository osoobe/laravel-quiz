<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Osoobe\Quiz\Enums\AttemptStatus;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Models\QuizInvitation;

trait HasQuizAttempts
{
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'user_id');
    }

    public function quizInvitations(): HasMany
    {
        return $this->hasMany(QuizInvitation::class, 'user_id');
    }

    public function bestScoreFor(Quiz $quiz): ?int
    {
        return $this->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->where('status', AttemptStatus::Completed->value)
            ->max('score');
    }
}
