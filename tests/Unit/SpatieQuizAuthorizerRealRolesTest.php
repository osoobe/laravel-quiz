<?php

use Illuminate\Support\Facades\Schema;
use Osoobe\Quiz\Auth\SpatieQuizAuthorizer;
use Osoobe\Quiz\Enums\QuizRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Workbench\App\Models\User;

/**
 * Mixes the real Spatie HasRoles trait into the package's fixture User model —
 * something the fixture model can't do directly, since spatie/laravel-permission is
 * a require-dev-only dependency of this package (the `gate` driver needs none of it)
 * and the trait class wouldn't exist wherever it isn't installed. Backed by the same
 * `users` table via inheritance, so it can load rows created through the normal User
 * factory. Complements AuthDriversTest's FakeSpatieUser (which fakes hasAnyRole() to
 * test SpatieQuizAuthorizer's delegation logic in isolation) by instead exercising
 * real spatie/laravel-permission role storage end to end.
 */
class SpatieRoleUser extends User
{
    use HasRoles;

    protected $table = 'users';

    protected $guard_name = 'web';
}

function makeSpatieRoleUser(): SpatieRoleUser
{
    return SpatieRoleUser::find(User::factory()->create()->getKey());
}

/**
 * spatie/laravel-permission ships its `roles`/`permissions` tables as a publishable
 * stub, not an auto-run migration — the host app is expected to `vendor:publish` it.
 * Rather than committing that migration into this app just for a test, run the stub
 * directly; RefreshDatabase wraps every test in a rolled-back transaction, so this
 * (and the roles seeded below) disappear again after each test.
 */
beforeEach(function () {
    if (! Schema::hasTable('roles')) {
        (require packagePath('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
    }

    foreach (QuizRole::cases() as $role) {
        Role::findOrCreate($role->value, 'web');
    }
});

it('grants staff access to a real user holding a QuizRole staff role', function (QuizRole $role) {
    $user = makeSpatieRoleUser();
    $user->assignRole($role->value);

    $authorizer = new SpatieQuizAuthorizer;

    expect($authorizer->isStaff($user))->toBeTrue();
    expect($authorizer->isInvitationManager($user))->toBe($role->isInvitationManager());
})->with([
    'owner' => [QuizRole::Owner],
    'admin' => [QuizRole::Admin],
    'moderator' => [QuizRole::Moderator],
]);

it('denies staff and invitation-manager access to a real user holding only the taker role', function () {
    $user = makeSpatieRoleUser();
    $user->assignRole(QuizRole::Taker->value);

    $authorizer = new SpatieQuizAuthorizer;

    expect($authorizer->isStaff($user))->toBeFalse();
    expect($authorizer->isInvitationManager($user))->toBeFalse();
});

it('denies a real user holding no roles at all', function () {
    $user = makeSpatieRoleUser();
    $authorizer = new SpatieQuizAuthorizer;

    expect($authorizer->isStaff($user))->toBeFalse();
    expect($authorizer->isInvitationManager($user))->toBeFalse();
});
