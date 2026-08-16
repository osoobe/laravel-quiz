<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Support\HasQuizAttempts;
use Workbench\Database\Factories\UserFactory;

/**
 * The package's test-only stand-in for a host app's own User model — mirrors
 * exactly what a real host implementing Osoobe\Quiz\Contracts\QuizUser looks like.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements QuizUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasQuizAttempts, Notifiable;

    public function quizDisplayName(): string
    {
        return $this->name;
    }

    public function quizAvatarUrl(): ?string
    {
        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
