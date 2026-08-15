<?php

namespace Osoobe\Quiz;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Osoobe\Quiz\Auth\GateQuizAuthorizer;
use Osoobe\Quiz\Auth\SpatieQuizAuthorizer;
use Osoobe\Quiz\Contracts\QuizAuthorizer;
use Osoobe\Quiz\Http\Middleware\EnsureQuizStaff;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizInvitation;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Osoobe\Quiz\Policies\QuizAttemptPolicy;
use Osoobe\Quiz\Policies\QuizCategoryPolicy;
use Osoobe\Quiz\Policies\QuizInvitationPolicy;
use Osoobe\Quiz\Policies\QuizPolicy;
use Osoobe\Quiz\Policies\QuizQuestionPolicy;
use Osoobe\Quiz\Policies\QuizTopicPolicy;
use Spatie\Permission\Models\Role as SpatieRole;

class QuizServiceProvider extends ServiceProvider
{
    private const POLICIES = [
        Quiz::class => QuizPolicy::class,
        QuizQuestion::class => QuizQuestionPolicy::class,
        QuizTopic::class => QuizTopicPolicy::class,
        QuizCategory::class => QuizCategoryPolicy::class,
        QuizAttempt::class => QuizAttemptPolicy::class,
        QuizInvitation::class => QuizInvitationPolicy::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/quiz.php', 'quiz');
    }

    public function boot(): void
    {
        // In boot(), not register(): every provider's register() phase — including a
        // host's own AppServiceProvider — has already run, so a host-side binding
        // always wins over this default regardless of provider load order.
        $this->bindDefaultAuthorizer();

        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        $this->app['router']->aliasMiddleware('quiz.staff', EnsureQuizStaff::class);
        $this->registerSanctumStatefulDomain();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'quiz');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'quiz');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/quiz.php' => config_path('quiz.php')], 'quiz-config');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'quiz-migrations');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/quiz')], 'quiz-views');
            $this->publishes([__DIR__.'/../resources/dist' => public_path('vendor/quiz')], 'quiz-assets');
            $this->publishes([
                __DIR__.'/../database/migration-stubs/add_quiz_support_to_table.php.stub' => database_path(
                    'migrations/'.date('Y_m_d_His').'_add_quiz_support_to_table.php'
                ),
            ], 'quiz-scope-migration-stub');
        }
    }

    private function bindDefaultAuthorizer(): void
    {
        if ($this->app->bound(QuizAuthorizer::class)) {
            return;
        }

        $this->app->singleton(QuizAuthorizer::class, fn () => $this->resolveAuthorizer());
    }

    /**
     * Public so it can be exercised directly in tests without re-running the rest
     * of boot() (route/view/migration registration).
     */
    public function resolveAuthorizer(): QuizAuthorizer
    {
        $driver = config('quiz.auth_driver');

        return match (true) {
            $driver === 'spatie' => new SpatieQuizAuthorizer(),
            $driver === 'gate' => new GateQuizAuthorizer(),
            $driver !== null => $this->app->make($driver),
            class_exists(SpatieRole::class) => new SpatieQuizAuthorizer(),
            default => new GateQuizAuthorizer(),
        };
    }

    /**
     * Sanctum's fromFrontend() check matches the Referer/Origin header against
     * config('sanctum.stateful') using a "{$host}/*" wildcard — a bare host with no
     * port (e.g. "localhost" from config('app.url')) never matches a real request's
     * "localhost:8000" or "127.0.0.1:34567", so it silently falls through to
     * unauthenticated on every dev port/proxy domain that isn't Sanctum's own
     * hardcoded defaults. Trusting the CURRENT request's own Host header — evaluated
     * fresh on each request in the typical (non-Octane) Laravel lifecycle — makes this
     * self-adapt to whatever domain/port the app is actually being served on, which is
     * what "any Laravel project, zero config" requires. This trusts the app's own
     * domain, not a third party: Referer/Origin still has to match it, which is exactly
     * what a legitimate same-origin SPA request looks like.
     */
    private function registerSanctumStatefulDomain(): void
    {
        $stateful = Config::get('sanctum.stateful', []);

        $hosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            $this->currentRequestHost(),
        ]);

        foreach ($hosts as $host) {
            if (! in_array($host, $stateful, true)) {
                $stateful[] = $host;
            }
        }

        Config::set('sanctum.stateful', $stateful);
    }

    private function currentRequestHost(): ?string
    {
        if ($this->app->runningInConsole() || ! $this->app->bound('request')) {
            return null;
        }

        return $this->app['request']->getHttpHost() ?: null;
    }
}
