<?php

namespace Osoobe\Quiz\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Workbench\App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    /**
     * config('quiz.user_model') itself comes from QUIZ_USER_MODEL in phpunit.xml —
     * this only needs to cover auth.php, which Workbench doesn't otherwise point at
     * the workbench User model.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
    }
}
