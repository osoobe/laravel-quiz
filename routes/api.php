<?php

use Illuminate\Support\Facades\Route;
use Osoobe\Quiz\Http\Controllers\Api\Admin\CategoryCrudController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\CategoryIoController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\InvitationController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\QuestionCrudController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\QuestionIoController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\QuizCrudController as AdminQuizCrudController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\TopicCrudController;
use Osoobe\Quiz\Http\Controllers\Api\Admin\TopicIoController;
use Osoobe\Quiz\Http\Controllers\Api\AttemptController;
use Osoobe\Quiz\Http\Controllers\Api\CatalogueController;
use Osoobe\Quiz\Http\Controllers\Api\LeaderboardController;
use Osoobe\Quiz\Http\Controllers\Api\ResultsController;
use Osoobe\Quiz\Http\Controllers\Api\TakerController;

Route::middleware(config('quiz.route.api_middleware', ['api']))
    ->prefix(config('quiz.route.api_prefix', 'api/quiz'))
    ->name('quiz.api.')
    ->group(function () {
        Route::get('quizzes', [CatalogueController::class, 'index'])->name('catalogue');
        Route::get('quizzes/{quiz}', [TakerController::class, 'show'])->name('show');

        Route::post('quizzes/{quiz}/attempts', [AttemptController::class, 'store'])->name('attempts.store');
        Route::patch('quizzes/{quiz}/attempts/{attempt}', [AttemptController::class, 'update'])->name('attempts.update');
        Route::post('quizzes/{quiz}/attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('attempts.submit');

        Route::get('quizzes/{quiz}/leaderboard', [LeaderboardController::class, 'show'])->name('leaderboard');

        Route::get('quizzes/{quiz}/results', [ResultsController::class, 'index'])->name('results.index');
        Route::delete('quizzes/{quiz}/results/{attempt}', [ResultsController::class, 'destroy'])->name('results.destroy');

        // Staff OR the quiz's own creator (object-level check inside the controller) —
        // deliberately outside the staff-only admin_middleware group below.
        Route::get('admin/quizzes/{quiz}', [AdminQuizCrudController::class, 'show'])->name('admin.quizzes.show');
        Route::match(['put', 'patch'], 'admin/quizzes/{quiz}', [AdminQuizCrudController::class, 'update'])->name('admin.quizzes.update');
        Route::delete('admin/quizzes/{quiz}', [AdminQuizCrudController::class, 'destroy'])->name('admin.quizzes.destroy');

        // Staff-invitation-managers OR the quiz's own creator — same reasoning.
        Route::get('admin/quizzes/{quiz}/invitations', [InvitationController::class, 'index'])->name('admin.invitations.index');
        Route::post('admin/quizzes/{quiz}/invitations', [InvitationController::class, 'store'])->name('admin.invitations.store');
        Route::delete('admin/quizzes/{quiz}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('admin.invitations.destroy');

        // Staff-only surfaces.
        Route::middleware(config('quiz.route.admin_middleware', ['quiz.staff']))
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('quizzes', [AdminQuizCrudController::class, 'index'])->name('quizzes.index');
                Route::post('quizzes', [AdminQuizCrudController::class, 'store'])->name('quizzes.store');

                Route::apiResource('questions', QuestionCrudController::class);
                Route::apiResource('topics', TopicCrudController::class);
                Route::apiResource('categories', CategoryCrudController::class);

                Route::get('questions-export', [QuestionIoController::class, 'export'])->name('questions.export');
                Route::post('questions-import', [QuestionIoController::class, 'import'])->name('questions.import');

                Route::get('categories-export', [CategoryIoController::class, 'export'])->name('categories.export');
                Route::post('categories-import', [CategoryIoController::class, 'import'])->name('categories.import');

                Route::get('topics-export', [TopicIoController::class, 'export'])->name('topics.export');
                Route::post('topics-import', [TopicIoController::class, 'import'])->name('topics.import');
            });
    });
