<?php

use App\Models\User;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Osoobe\Quiz\Enums\QuizRole;

return [

    // The host app's User model. Must implement Osoobe\Quiz\Contracts\QuizUser.
    'user_model' => env('QUIZ_USER_MODEL', User::class),

    // null       => auto-detect (spatie/laravel-permission if installed, else gate)
    // 'spatie'   => Osoobe\Quiz\Auth\SpatieQuizAuthorizer
    // 'gate'     => Osoobe\Quiz\Auth\GateQuizAuthorizer
    // FQCN       => a class implementing Osoobe\Quiz\Contracts\QuizAuthorizer
    'auth_driver' => env('QUIZ_AUTH_DRIVER'),

    // Role names treated as quiz staff when auth_driver = spatie (full manage + attempt-limit bypass).
    // When auth_driver = gate, define a `quiz.staff` Gate instead — this array is unused.
    'staff_roles' => [
        QuizRole::Owner->value,
        QuizRole::Admin->value,
        QuizRole::Moderator->value,
    ],

    // Roles allowed to manage private-quiz invitations, in addition to the quiz creator.
    // When auth_driver = gate, define a `quiz.manage-invitations` Gate instead.
    'invitation_manager_roles' => [
        QuizRole::Owner->value,
        QuizRole::Admin->value,
    ],

    'route' => [
        'prefix' => 'quizzes',            // public SPA prefix (catalogue, taker, admin, ...)
        'api_prefix' => 'api/quiz',        // JSON API prefix
        'assets_prefix' => 'quiz-assets',  // built JS/CSS asset prefix

        'web_middleware' => ['web'],
        'api_middleware' => ['api', EnsureFrontendRequestsAreStateful::class],

        // Merged onto admin-only API routes, in addition to api_middleware.
        'admin_middleware' => ['quiz.staff'],
    ],

    'view' => [
        // Blade view that renders the SPA shell. Publish + override for deeper host chrome integration.
        'shell' => 'quiz::app',
    ],

    'defaults' => [
        'question_count' => 10,
        'max_attempts' => 1,
        'randomize_questions' => true,
        'audience' => 'everyone',
    ],

    // Count in-progress/abandoned attempts toward max_attempts.
    'count_incomplete_attempts' => true,

    'leaderboard' => [
        'limit' => 50,
        'best_per_user' => false, // false = every completed attempt listed (a user can appear more than once)
    ],

    // Prefix used for quizzes attached to a host entity (event/course/cohort), e.g. "scope-{id}".
    // Scoped quizzes are hidden from the public catalogue.
    'scoped_prefix' => 'scope-',

];
