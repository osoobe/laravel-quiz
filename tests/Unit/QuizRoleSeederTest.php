<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Osoobe\Quiz\Database\Seeders\QuizRoleSeeder;
use Osoobe\Quiz\Enums\QuizRole;
use Spatie\Permission\Models\Role;

it('creates a Spatie role for every QuizRole case when the permission tables exist', function () {
    (require packagePath('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();

    (new QuizRoleSeeder)->run();

    foreach (QuizRole::cases() as $role) {
        expect(Role::where('name', $role->value)->where('guard_name', 'web')->exists())->toBeTrue();
    }
});

it('is idempotent when run more than once', function () {
    (require packagePath('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();

    (new QuizRoleSeeder)->run();
    (new QuizRoleSeeder)->run();

    expect(Role::where('guard_name', 'web')->count())->toBe(count(QuizRole::cases()));
});

it('warns instead of throwing when the permission tables are not migrated', function () {
    expect(Schema::hasTable('roles'))->toBeFalse();

    Log::shouldReceive('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'permission'));

    (new QuizRoleSeeder)->run();
});
