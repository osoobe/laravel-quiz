<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Auth\GateQuizAuthorizer;
use Osoobe\Quiz\Auth\SpatieQuizAuthorizer;
use Osoobe\Quiz\Contracts\QuizAuthorizer;
use Osoobe\Quiz\QuizServiceProvider;
use Workbench\App\Models\User;

/**
 * A minimal spatie-shaped stub — deliberately not the real HasRoles trait, so this
 * test exercises SpatieQuizAuthorizer's own delegation logic in isolation rather
 * than spatie/laravel-permission's implementation.
 */
class FakeSpatieUser implements Authenticatable
{
    use Illuminate\Auth\Authenticatable;

    public function __construct(private array $roles = []) {}

    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($this->roles, $roles)) > 0;
    }

    public function can($ability, $arguments = []): bool
    {
        return in_array($ability, ['some.ability'], true);
    }
}

it('SpatieQuizAuthorizer delegates staff/invitation checks to hasAnyRole using configured role lists', function () {
    config(['quiz.staff_roles' => ['admin'], 'quiz.invitation_manager_roles' => ['moderator']]);

    $staff = new FakeSpatieUser(['admin']);
    $nobody = new FakeSpatieUser(['member']);

    $authorizer = new SpatieQuizAuthorizer;

    expect($authorizer->isStaff($staff))->toBeTrue();
    expect($authorizer->isStaff($nobody))->toBeFalse();
    expect($authorizer->isInvitationManager(new FakeSpatieUser(['moderator'])))->toBeTrue();
});

it('GateQuizAuthorizer delegates to Gate::define(quiz.staff) and fails closed when undefined', function () {
    // A real Eloquent user, not the FakeSpatieUser stub — spatie/laravel-permission's
    // service provider registers a Gate::before() hook (for its wildcard super-admin
    // check) that requires a genuine Authorizable model, which the minimal stub isn't.
    $user = User::factory()->create();
    $authorizer = new GateQuizAuthorizer;

    expect($authorizer->isStaff($user))->toBeFalse(); // undefined gate -> denies, does not throw

    Gate::define('quiz.staff', fn () => true);

    expect($authorizer->isStaff($user))->toBeTrue();
});

it('resolves the spatie driver when quiz.auth_driver is explicitly spatie', function () {
    config(['quiz.auth_driver' => 'spatie']);

    $resolved = (new QuizServiceProvider(app()))->resolveAuthorizer();

    expect($resolved)->toBeInstanceOf(SpatieQuizAuthorizer::class);
});

it('resolves the gate driver when quiz.auth_driver is explicitly gate', function () {
    config(['quiz.auth_driver' => 'gate']);

    $resolved = (new QuizServiceProvider(app()))->resolveAuthorizer();

    expect($resolved)->toBeInstanceOf(GateQuizAuthorizer::class);
});

it('auto-detects the spatie driver when no explicit driver is set and spatie is installed', function () {
    config(['quiz.auth_driver' => null]);

    // spatie/laravel-permission is installed (dev dependency) in this test environment.
    $resolved = (new QuizServiceProvider(app()))->resolveAuthorizer();

    expect($resolved)->toBeInstanceOf(SpatieQuizAuthorizer::class);
});

it('lets a host binding registered before boot() win over the package default', function () {
    $custom = new GateQuizAuthorizer;
    app()->instance(QuizAuthorizer::class, $custom);

    expect(app()->bound(QuizAuthorizer::class))->toBeTrue();
    expect(app(QuizAuthorizer::class))->toBe($custom);
});
