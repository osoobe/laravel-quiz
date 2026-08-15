# osoobe/laravel-quiz

Full quiz management, taking, and leaderboard package for Laravel — quizzes, questions,
topics, categories, attempts, invitations, and an admin manager. The frontend is a
self-contained React SPA that ships pre-built inside the package: **no host build step,
no Inertia, no React of your own required.** The host only needs a JSON API surface and
a browser.

## Install

```bash
composer require osoobe/laravel-quiz
php artisan vendor:publish --tag=quiz-config   # optional — edit config/quiz.php
php artisan migrate
```

Visit `/quizzes` (configurable — see below). That's it: the React bundle is served by
the package's own route, reading straight from its own `resources/dist/`.

### Optional: publish the built assets as real static files

By default the package serves its JS/CSS through a Laravel route (`AssetController`),
so there is nothing to publish and upgrades are always self-consistent. If you want
nginx/a CDN to serve those files directly instead of bootstrapping Laravel per asset
request:

```bash
php artisan vendor:publish --tag=quiz-assets
```

Published files under `public/vendor/quiz/` are automatically preferred over the
package's own copy once present — re-run the publish command after upgrading the
package if you use this.

## Configuration (`config/quiz.php`)

| Key | Purpose |
| --- | --- |
| `user_model` | Host `User` model. Must implement `Osoobe\Quiz\Contracts\QuizUser` (`getKey()`, `quizDisplayName()`, `quizAvatarUrl()`). |
| `auth_driver` | `null` (auto-detect), `'spatie'`, `'gate'`, or a class implementing `Osoobe\Quiz\Contracts\QuizAuthorizer`. See below. |
| `staff_roles` / `invitation_manager_roles` | Role names checked by the `spatie` driver. Ignored by `gate`. |
| `route.*` | Prefixes and middleware — see below. |
| `view.shell` | Blade view for the SPA shell (`quiz::app` by default — a standalone HTML page). Publish with `--tag=quiz-views` to customise. |
| `defaults`, `count_incomplete_attempts`, `leaderboard`, `scoped_prefix` | Quiz behaviour defaults — see inline comments in the config file. |

### Host `User` model contract

```php
use Osoobe\Quiz\Contracts\QuizUser;
use Osoobe\Quiz\Support\HasQuizAttempts;

class User extends Authenticatable implements QuizUser
{
    use HasQuizAttempts;

    public function quizDisplayName(): string { return $this->name; }
    public function quizAvatarUrl(): ?string { return $this->avatar_url ?? null; }
}
```

### Authorization — pick one, or bring your own

The package needs to answer two questions: *is this user quiz staff* (full manage
access + attempt-limit bypass) and *can this user manage invitations for a private
quiz*. Everything else (ownership — "is this the quiz's creator") is a plain database
comparison and isn't part of this abstraction.

- **`spatie` driver** — uses `spatie/laravel-permission`'s `hasAnyRole()` against
  `quiz.staff_roles` / `quiz.invitation_manager_roles`. Auto-selected if
  `spatie/laravel-permission` is installed and `auth_driver` is left `null`. Review the
  default role names before relying on auto-detection — a pre-existing role literally
  named `admin` would silently become quiz staff too.
- **`gate` driver** — delegates to `Gate::allows('quiz.staff')` /
  `Gate::allows('quiz.manage-invitations')`. Define those gates yourself:
  ```php
  Gate::define('quiz.staff', fn ($user) => $user->is_admin);
  ```
  Undefined gates simply deny (fail closed) rather than throwing.
- **Fully custom** — implement `Osoobe\Quiz\Contracts\QuizAuthorizer` (3 methods:
  `isStaff`, `isInvitationManager`, `can`) and bind it in your own
  `AppServiceProvider::register()`:
  ```php
  $this->app->bind(\Osoobe\Quiz\Contracts\QuizAuthorizer::class, \App\Services\MyQuizAuthorizer::class);
  ```
  A binding registered in `register()` always wins over the package's default,
  regardless of provider load order.

### Middleware

```php
'route' => [
    'prefix' => 'quizzes', 'api_prefix' => 'api/quiz', 'assets_prefix' => 'quiz-assets',
    'web_middleware' => ['web'],
    'api_middleware' => ['api', \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class],
    'admin_middleware' => ['quiz.staff'],
],
```

All three are plain arrays — compose freely (`'admin_middleware' => ['quiz.staff', 'throttle:admin']`).
`quiz.staff` is a package-registered middleware alias that calls the bound
`QuizAuthorizer::isStaff()` — there's no separate role-check mechanism to keep in sync.

### Authentication for the SPA

The React app talks to the API over the same origin using Sanctum's SPA (cookie/session)
mode — no CORS configuration, no token storage. The package applies
`EnsureFrontendRequestsAreStateful` itself and auto-appends `config('app.url')`'s host to
`sanctum.stateful` on boot, so there's nothing to configure in the common case.

## Developing the frontend

The React source lives in `resources/js-src/`, built by this package's own toolchain —
it never touches a host app's `package.json`/build config.

```bash
cd vendor/osoobe/laravel-quiz   # or the devpackage/ path during local development
npm install
npm run build   # writes resources/dist/, which the package commits and ships
```

## Optional: attaching a quiz to a host entity (event, course, cohort…)

```php
use Osoobe\Quiz\Contracts\QuizScope;
use Osoobe\Quiz\Support\HasQuiz;

class Event extends Model implements QuizScope
{
    use HasQuiz;

    public function quizScopeIdentifier(): string { return $this->id; }
}
```

Publish and adapt the migration stub, then create the quiz with
`audience' => $event->scopedQuizAudience()` — scoped quizzes are automatically hidden
from the public catalogue.
