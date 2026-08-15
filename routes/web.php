<?php

use Illuminate\Support\Facades\Route;
use Osoobe\Quiz\Http\Controllers\AssetController;
use Osoobe\Quiz\Http\Controllers\ShellController;

Route::middleware(config('quiz.route.web_middleware', ['web']))->group(function () {
    Route::get(config('quiz.route.assets_prefix', 'quiz-assets').'/{path}', AssetController::class)
        ->where('path', '.*')
        ->name('quiz.assets');

    Route::get(config('quiz.route.prefix', 'quizzes').'/{any?}', ShellController::class)
        ->where('any', '.*')
        ->name('quiz.shell');
});
