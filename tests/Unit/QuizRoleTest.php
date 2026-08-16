<?php

use Osoobe\Quiz\Enums\QuizRole;

it('matches the default config(quiz.staff_roles) set', function () {
    expect(array_map(fn (QuizRole $role) => $role->value, QuizRole::staffRoles()))
        ->toBe(config('quiz.staff_roles'));
});

it('matches the default config(quiz.invitation_manager_roles) set', function () {
    expect(array_map(fn (QuizRole $role) => $role->value, QuizRole::invitationManagerRoles()))
        ->toBe(config('quiz.invitation_manager_roles'));
});

it('treats owner, admin, and moderator as staff but not taker', function () {
    expect(QuizRole::Owner->isStaff())->toBeTrue();
    expect(QuizRole::Admin->isStaff())->toBeTrue();
    expect(QuizRole::Moderator->isStaff())->toBeTrue();
    expect(QuizRole::Taker->isStaff())->toBeFalse();
});

it('treats only owner and admin as invitation managers', function () {
    expect(QuizRole::Owner->isInvitationManager())->toBeTrue();
    expect(QuizRole::Admin->isInvitationManager())->toBeTrue();
    expect(QuizRole::Moderator->isInvitationManager())->toBeFalse();
    expect(QuizRole::Taker->isInvitationManager())->toBeFalse();
});

it('has a human-readable label for every case', function () {
    expect(QuizRole::Owner->label())->toBe('Quiz Owner');
    expect(QuizRole::Admin->label())->toBe('Quiz Admin');
    expect(QuizRole::Moderator->label())->toBe('Quiz Moderator');
    expect(QuizRole::Taker->label())->toBe('Quiz Taker');
});
