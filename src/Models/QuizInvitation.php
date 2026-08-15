<?php

namespace Osoobe\Quiz\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizInvitation extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'quiz_invitations';

    protected $fillable = ['quiz_id', 'user_id', 'invited_by'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('quiz.user_model'), 'user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(config('quiz.user_model'), 'invited_by');
    }
}
