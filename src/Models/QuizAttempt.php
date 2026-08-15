<?php

namespace Osoobe\Quiz\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Osoobe\Quiz\Enums\AttemptStatus;

class QuizAttempt extends Model
{
    use HasUuids;

    protected $table = 'quiz_attempts';

    protected $fillable = [
        'quiz_id', 'user_id', 'started_at', 'completed_at', 'score',
        'total_questions', 'correct_answers', 'answers', 'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'integer',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'answers' => 'array',
        'status' => AttemptStatus::class,
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('quiz.user_model'), 'user_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AttemptStatus::Completed->value);
    }
}
