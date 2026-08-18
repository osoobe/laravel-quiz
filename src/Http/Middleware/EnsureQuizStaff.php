<?php

namespace Osoobe\Quiz\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Osoobe\Quiz\Services\QuizAccess;

class EnsureQuizStaff
{
    public function __construct(private QuizAccess $access) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $this->access->isStaff($user)) {
            return response()->json([
                'message' => 'This action is unauthorized.',
                'error_code' => 'quiz.access_denied',
            ], 403);
        }

        return $next($request);
    }
}
