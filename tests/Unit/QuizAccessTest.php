<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizInvitation;
use Osoobe\Quiz\Services\QuizAccess;
use Workbench\App\Models\User;

beforeEach(function () {
    config(['quiz.auth_driver' => 'gate']);
});

it('allows a guest to view an active everyone quiz', function () {
    $quiz = Quiz::factory()->create(['audience' => 'everyone', 'is_active' => true]);

    expect(app(QuizAccess::class)->allows(null, $quiz))->toBeTrue();
});

it('denies a guest an inactive quiz even when audience is everyone', function () {
    $quiz = Quiz::factory()->create(['audience' => 'everyone', 'is_active' => false]);

    expect(app(QuizAccess::class)->allows(null, $quiz))->toBeFalse();
});

it('lets staff access an inactive quiz to preview it', function () {
    Gate::define('quiz.staff', fn () => true);
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['audience' => 'everyone', 'is_active' => false]);

    expect(app(QuizAccess::class)->allows($user, $quiz))->toBeTrue();
});

it('denies logged_in audience to guests but allows any authenticated user', function () {
    $quiz = Quiz::factory()->create(['audience' => 'logged_in']);
    $user = User::factory()->create();

    expect(app(QuizAccess::class)->allows(null, $quiz))->toBeFalse();
    expect(app(QuizAccess::class)->allows($user, $quiz))->toBeTrue();
});

it('denies a private quiz unless the user has an invitation', function () {
    $quiz = Quiz::factory()->private()->create();
    $invited = User::factory()->create();
    $notInvited = User::factory()->create();

    QuizInvitation::create([
        'quiz_id' => $quiz->id,
        'user_id' => (string) $invited->getKey(),
        'invited_by' => (string) $quiz->created_by,
    ]);

    expect(app(QuizAccess::class)->allows($invited, $quiz))->toBeTrue();
    expect(app(QuizAccess::class)->allows($notInvited, $quiz))->toBeFalse();
    expect(app(QuizAccess::class)->allows(null, $quiz))->toBeFalse();
});

it('always allows the quiz creator, regardless of audience', function () {
    $creator = User::factory()->create();
    $quiz = Quiz::factory()->private()->create(['created_by' => (string) $creator->getKey()]);

    expect(app(QuizAccess::class)->allows($creator, $quiz))->toBeTrue();
});

it('treats a scoped audience as visible to any authenticated user, hidden from guests', function () {
    $quiz = Quiz::factory()->create(['audience' => 'scope-'.Str::uuid()]);
    $user = User::factory()->create();

    expect(app(QuizAccess::class)->allows(null, $quiz))->toBeFalse();
    expect(app(QuizAccess::class)->allows($user, $quiz))->toBeTrue();
});
