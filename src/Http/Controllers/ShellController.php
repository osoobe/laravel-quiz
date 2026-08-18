<?php

namespace Osoobe\Quiz\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Osoobe\Quiz\Contracts\QuizAuthorizer;
use Osoobe\Quiz\Support\ViteManifest;

class ShellController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $asset = ViteManifest::entry();

        return view(config('quiz.view.shell', 'quiz::app'), [
            'quizJs' => $asset['js'] ? route('quiz.assets', ['path' => $asset['js']]) : null,
            'quizCss' => collect($asset['css'])->map(fn ($path) => route('quiz.assets', ['path' => $path]))->all(),
            'bootstrap' => [
                'csrfToken' => csrf_token(),
                'apiBase' => url(config('quiz.route.api_prefix', 'api/quiz')),
                'basePath' => '/'.trim(config('quiz.route.prefix', 'quizzes'), '/'),
                'user' => $user ? [
                    'id' => $user->getKey(),
                    'name' => $user->quizDisplayName(),
                    'avatarUrl' => $user->quizAvatarUrl(),
                    'isStaff' => app(QuizAuthorizer::class)->isStaff($user),
                ] : null,
                'loginUrl' => Route::has('login') ? route('login') : null,
                'flash' => [
                    'message' => session('message'),
                    'error' => session('error'),
                    'bulk_errors' => session('bulk_errors'),
                ],
            ],
        ]);
    }
}
