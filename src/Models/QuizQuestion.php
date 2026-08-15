<?php

namespace Osoobe\Quiz\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Osoobe\Quiz\Database\Factories\QuizQuestionFactory;
use Osoobe\Quiz\Enums\QuizDifficulty;
use Osoobe\Quiz\Enums\QuizQuestionType;
use Osoobe\Quiz\Support\AnswerListCast;
use Osoobe\Quiz\Support\HasItemCode;

class QuizQuestion extends Model
{
    use HasFactory, HasItemCode, HasUuids;

    protected static function newFactory(): QuizQuestionFactory
    {
        return QuizQuestionFactory::new();
    }

    protected $table = 'quiz_questions';

    protected $fillable = [
        'itemcode', 'topic_id', 'category_id', 'question', 'description',
        'difficulty', 'question_type', 'answers', 'is_active', 'created_by',
    ];

    protected $casts = [
        'answers' => AnswerListCast::class,
        'is_active' => 'boolean',
        'difficulty' => QuizDifficulty::class,
        'question_type' => QuizQuestionType::class,
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuizTopic::class, 'topic_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
