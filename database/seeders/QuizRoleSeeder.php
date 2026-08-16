<?php

namespace Osoobe\Quiz\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Osoobe\Quiz\Enums\QuizRole;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Creates the Spatie roles behind every `QuizRole` case (`quiz_owner`, `quiz_admin`,
 * `quiz_moderator`, `quiz_taker`), so the defaults in `config('quiz.staff_roles')` /
 * `config('quiz.invitation_manager_roles')` resolve to real, assignable roles under
 * the `spatie` auth driver.
 *
 * spatie/laravel-permission is only a suggested dependency of this package (the
 * `gate` driver needs none of this), so this seeder must never fatal a host's
 * `db:seed` run just because that package — or its migrated tables — aren't present.
 * Both failure modes are caught and reported as a warning instead.
 */
class QuizRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Role::class)) {
            $this->report(
                'Skipped: spatie/laravel-permission is not installed. Run '
                .'`composer require spatie/laravel-permission` before seeding quiz roles.'
            );

            return;
        }

        $guard = config('auth.defaults.guard', 'web');

        try {
            foreach (QuizRole::cases() as $role) {
                Role::findOrCreate($role->value, $guard);
            }
        } catch (Throwable $e) {
            $this->report(
                "Skipped: couldn't create quiz roles ({$e->getMessage()}). Make sure "
                ."spatie/laravel-permission's tables are migrated — "
                .'`php artisan vendor:publish --tag="permission-migrations"` then `php artisan migrate`.'
            );

            return;
        }

        $this->report(
            'Seeded quiz roles ('.$guard.' guard): '
            .implode(', ', array_map(fn (QuizRole $role) => $role->value, QuizRole::cases())),
            error: false
        );
    }

    private function report(string $message, bool $error = true): void
    {
        if (isset($this->command)) {
            $error ? $this->command->warn($message) : $this->command->info($message);

            return;
        }

        $error ? Log::warning($message) : Log::info($message);
    }
}
