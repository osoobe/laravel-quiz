<?php

namespace Osoobe\Quiz\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Osoobe\Quiz\Database\Factories\QuizTopicFactory;
use Osoobe\Quiz\Support\HasItemCode;

class QuizTopic extends Model
{
    use HasFactory, HasItemCode, HasUuids;

    protected static function newFactory(): QuizTopicFactory
    {
        return QuizTopicFactory::new();
    }

    protected $table = 'quiz_topics';

    protected $fillable = ['itemcode', 'name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'topic_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'topic_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
