<?php

namespace Osoobe\Quiz\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Osoobe\Quiz\Database\Factories\QuizFactory;
use Osoobe\Quiz\Enums\QuizAudience;
use Osoobe\Quiz\Enums\QuizDifficulty;
use Osoobe\Quiz\Services\QuizAccess;
use Osoobe\Quiz\Support\HasItemCode;

class Quiz extends Model
{
    use HasFactory, HasItemCode, HasUuids;

    protected static function newFactory(): QuizFactory
    {
        return QuizFactory::new();
    }

    protected $table = 'quizzes';

    protected $fillable = [
        'itemcode', 'name', 'description', 'topic_id', 'category_id', 'topic_ids', 'category_ids',
        'difficulty', 'question_count', 'randomize_questions', 'time_limit_minutes',
        'max_attempts', 'is_active', 'audience', 'created_by',
    ];

    protected $casts = [
        'topic_ids' => 'array',
        'category_ids' => 'array',
        'difficulty' => QuizDifficulty::class,
        'question_count' => 'integer',
        'randomize_questions' => 'boolean',
        'time_limit_minutes' => 'integer',
        'max_attempts' => 'integer',
        'is_active' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuizTopic::class, 'topic_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'category_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(QuizInvitation::class);
    }

    public function isScoped(): bool
    {
        return QuizAudience::isScoped($this->audience);
    }

    /**
     * Excludes quizzes attached to a host entity (audience = scope-{id}) — those
     * are surfaced on the owning entity's own page, not the general catalogue.
     */
    public function scopeCatalogue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('audience', 'not like', config('quiz.scoped_prefix', 'scope-').'%');
    }

    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): Builder
    {
        if ($user && app(QuizAccess::class)->isStaff($user)) {
            return $query;
        }

        $scopedPrefix = config('quiz.scoped_prefix', 'scope-');

        return $query->where(function (Builder $q) use ($user, $scopedPrefix) {
            $q->where(fn (Builder $q) => $q->where('is_active', true)->where('audience', QuizAudience::Everyone->value));

            if ($user) {
                $q->orWhere('created_by', $user->getKey())
                    ->orWhere(fn (Builder $q) => $q->where('is_active', true)
                        ->where(fn (Builder $q) => $q->where('audience', QuizAudience::LoggedIn->value)
                            ->orWhere('audience', 'like', $scopedPrefix.'%')))
                    ->orWhere(fn (Builder $q) => $q->where('is_active', true)
                        ->where('audience', QuizAudience::Private->value)
                        ->whereHas('invitations', fn (Builder $q) => $q->where('user_id', $user->getKey())));
            }
        });
    }
}
