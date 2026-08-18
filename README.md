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
| `staff_roles` / `invitation_manager_roles` | Role names checked by the `spatie` driver. Ignored by `gate`. Default values come from `Osoobe\Quiz\Enums\QuizRole` — see below. |
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
  `spatie/laravel-permission` is installed and `auth_driver` is left `null`. The default
  role names are namespaced (`quiz_owner`, `quiz_admin`, `quiz_moderator`) specifically to
  avoid colliding with a host's own generic `admin`/`moderator` roles — still worth a
  quick review of `staff_roles` before relying on auto-detection if your app's roles
  happen to use the same names.
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

#### `QuizRole` — the canonical role names

`Osoobe\Quiz\Enums\QuizRole` is the source of truth for the role-name strings the
`spatie` driver checks against. `config('quiz.staff_roles')` and
`config('quiz.invitation_manager_roles')` are seeded from it by default:

```php
use Osoobe\Quiz\Enums\QuizRole;

QuizRole::Owner->value;      // 'quiz_owner'
QuizRole::Admin->value;      // 'quiz_admin'
QuizRole::Moderator->value;  // 'quiz_moderator'
QuizRole::Taker->value;      // 'quiz_taker' — not staff; the default/implicit role

QuizRole::Owner->label();          // 'Quiz Owner' — for admin-UI role pickers
QuizRole::Owner->isStaff();        // true
QuizRole::Moderator->isInvitationManager(); // false — moderators can't manage invitations by default

QuizRole::staffRoles();             // [Owner, Admin, Moderator]
QuizRole::invitationManagerRoles(); // [Owner, Admin]
```

Assign these as real Spatie roles on your `User` model (e.g. in a seeder:
`$user->assignRole(QuizRole::Admin->value)`) rather than typing the raw strings —
`QuizRole` is the single place that name changes propagate from. There is no `staff`
column anywhere; standing is purely whatever roles the host app has assigned.

#### Seeding the roles: `QuizRoleSeeder`

The roles themselves (`quiz_owner`, `quiz_admin`, `quiz_moderator`, `quiz_taker`) still
have to exist in Spatie's `roles` table before anyone can be assigned one —
`Osoobe\Quiz\Database\Seeders\QuizRoleSeeder` creates all four, `findOrCreate()`'d
against `config('auth.defaults.guard')` so it's safe to run repeatedly. Call it from
your own `DatabaseSeeder`:

```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call(\Osoobe\Quiz\Database\Seeders\QuizRoleSeeder::class);
}
```

or run it on its own:

```bash
php artisan db:seed --class="Osoobe\Quiz\Database\Seeders\QuizRoleSeeder"
```

It's a no-op (with a warning, not a crash) if `spatie/laravel-permission` isn't
installed, or if it's installed but its tables haven't been migrated yet — both are
expected states for apps using the `gate` driver, which needs neither. If you hit the
second case and you do want the `spatie` driver, publish and run its migration first:

```bash
php artisan vendor:publish --tag="permission-migrations"
php artisan migrate
```

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

### Model policies

`QuizServiceProvider::boot()` registers a standard Laravel policy for every quiz
model, so you can authorize them the normal way — `$user->can(...)`, `@can(...)` in
Blade, `Gate::authorize(...)`, route model binding + `$this->authorize(...)` in your
own controllers — from your own host-app code:

| Model | Policy | Abilities |
| --- | --- | --- |
| `Quiz` | `QuizPolicy` | `viewAny` (public), `view` (audience/ownership/staff-aware — see `QuizAccess::allows()`), `create` (staff only), `update`/`delete` (staff or the quiz's creator), `manageInvitations` (invitation manager or creator), `viewResults` (staff or creator) |
| `QuizQuestion` | `QuizQuestionPolicy` | `viewAny` (any authenticated user — guests are denied automatically, the method takes a non-nullable `$user`), `view` (active, or staff), `create`/`update`/`delete` (staff only) |
| `QuizTopic` | `QuizTopicPolicy` | `viewAny` (public, guests included), `view` (active, or staff — guests explicitly allowed through), `create`/`update`/`delete` (staff only) |
| `QuizCategory` | `QuizCategoryPolicy` | same shape as `QuizTopic` |
| `QuizAttempt` | `QuizAttemptPolicy` | `view` (the attempt's own user, or staff), `create` (per `QuizAccess::allows()` against the target quiz), `update` (owner only, and only while `in_progress` — a completed/abandoned attempt can never be rewritten), `delete` (staff or the quiz's creator) |
| `QuizInvitation` | `QuizInvitationPolicy` | `viewAny`/`create` (invitation manager or quiz creator), `view` (the invitee, invitation manager, or quiz creator), `delete` (invitation manager or quiz creator) |

None of these policies talk to Spatie (or Gates) directly — every "is this user
staff/an invitation manager" check goes through the same `QuizAccess` service the rest
of the package uses, which delegates to whichever `QuizAuthorizer` is bound (`spatie`,
`gate`, or custom). So `$user->can('update', $quiz)` and the `spatie` driver's
`hasAnyRole(config('quiz.staff_roles'))` check are the same decision — a user with the
`quiz_admin` role from `QuizRoleSeeder` passes both.

**The package's own JSON API controllers don't call these policies.** They inject
`QuizAccess` directly and throw a `QuizAccessDeniedException` (which renders its own
403 JSON response) on failure — functionally the same checks the policies express,
just invoked without going through `Gate`. The policies exist for *your* code: reach
for them in your own controllers, Blade views, or tests instead of re-deriving quiz
access rules by hand.

```php
// In your own code — not required by the package's own routes/controllers
Gate::authorize('update', $quiz);
$request->user()->can('manageInvitations', $quiz);
```

### Authentication for the SPA

The React app talks to the API over the same origin using Sanctum's SPA (cookie/session)
mode — no CORS configuration, no token storage. The package applies
`EnsureFrontendRequestsAreStateful` itself and auto-appends `config('app.url')`'s host to
`sanctum.stateful` on boot, so there's nothing to configure in the common case.

### Embedding the shell in your own layout/chrome

The SPA doesn't require Inertia or React on the host — `ShellController` renders one
Blade view (`config('quiz.view.shell')`, `quiz::app` by default) that mounts the whole
app into a single `<div id="quiz-root">`. That view is already a normal, overridable
Blade view, so a host can make the quiz section visually part of the app — same nav,
same header, same theme — without the package depending on how the host renders
anything else:

```bash
php artisan vendor:publish --tag=quiz-views
```

This copies `resources/views` to `resources/views/vendor/quiz/`, which Laravel's view
resolver checks before falling back to the package's own copy. Edit
`resources/views/vendor/quiz/app.blade.php` to wrap the required elements in your own
layout, e.g. a Blade layout component:

```blade
<x-app-layout>
    <meta name="csrf-token" content="{{ $bootstrap['csrfToken'] }}">
    @foreach ($quizCss as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach

    <div id="quiz-root"></div>
    <script>window.QuizConfig = {!! \Illuminate\Support\Js::from($bootstrap) !!};</script>
    @if ($quizJs)
        <script type="module" src="{{ $quizJs }}"></script>
    @endif
</x-app-layout>
```

The four elements above (`csrf-token` meta tag, the `$quizCss` stylesheets, the
`#quiz-root` mount point, and `window.QuizConfig`) are the only things the React app
actually needs — everything else (nav, header, footer, dark-mode toggle, whatever
your layout already renders) is free real estate around it. Re-run the publish
command after upgrading the package if you've customized this file.

`$bootstrap` (exposed to the frontend as `window.QuizConfig`) also mirrors your app's
own flash session keys — `message`, `error`, and `bulk_errors` — the same three keys
used project-wide (see the root `CLAUDE.md`), so a redirect *into* the SPA after a
host-side action (e.g. `redirect()->route('quiz.shell', ['any' => $quiz->id])->with('message', 'Invitation sent')`)
surfaces as a toast inside the SPA exactly like it would in a Blade or Inertia page.
No wiring needed on the package side beyond what's already there.

## Developing the frontend

The React source lives in `resources/js-src/`, built by this package's own toolchain —
it never touches a host app's `package.json`/build config.

```bash
cd vendor/osoobe/laravel-quiz   # or the devpackage/ path during local development
npm install
npm run build   # writes resources/dist/, which the package commits and ships
```

## Running the tests

The package's Pest suite is fully standalone — it boots itself via
[Orchestra Testbench](https://packages.tools/testbench/) and
[Workbench](https://packages.tools/testbench/workbench.html) rather than depending on
a host Laravel app, so it runs the same whether or not this package is installed
anywhere yet:

```bash
cd devpackage/laravel-quiz   # or wherever this package lives once extracted
composer install
composer test                # or: vendor/bin/pest
```

A few things worth knowing if you're touching the test setup itself:

- `tests/TestCase.php` extends `Orchestra\Testbench\TestCase` and pulls in
  `WithWorkbench` — `testbench.yaml` lists `Osoobe\Quiz\QuizServiceProvider` and
  `Spatie\Permission\PermissionServiceProvider` as the providers to boot, and points
  at this package's own `database/migrations` in addition to `workbench/database/migrations`.
- `workbench/app/Models/User.php` is a minimal stand-in for a host app's own `User`
  model — it implements `QuizUser` and uses `HasQuizAttempts`, exactly like a real
  host would, so the tests exercise the actual contract rather than a shortcut.
- `spatie/laravel-permission` is a `require-dev` dependency of the package (not a
  runtime `require` — the `gate` driver needs none of it), so it's always available
  under test; the two suites that need real Spatie role storage
  (`SpatieQuizAuthorizerRealRolesTest`, `QuizRoleSeederTest`) load its
  `create_permission_tables` migration stub directly in a `beforeEach()`, since Spatie
  ships it as a publishable file rather than an auto-run migration.
- `tests/Fixtures/quizdata/` holds the real sample topics/categories/questions data
  used by `SampleDataImportTest` and `ImportAllDataRoundTripTest` to exercise the
  bulk-import endpoints against realistic volume, not synthetic factory data.
- CI lives at `.github/workflows/tests.yml` in this package's own repo — a plain
  `composer install && vendor/bin/pest` job, plus a frontend build/typecheck job and
  an advisory (non-blocking) Pint job.

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
