<?php

namespace Osoobe\Quiz\Http\Controllers\Api;

use Illuminate\Http\Request;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Http\Resources\ResultsAttemptResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Services\QuizAccess;

class ResultsController
{
    public function __construct(private QuizAccess $access) {}

    public function index(Request $request, Quiz $quiz)
    {
        $this->assertViewer($request, $quiz);

        $attempts = $quiz->attempts()->with('user')->latest('started_at')->paginate(50);

        return ResultsAttemptResource::collection($attempts);
    }

    public function destroy(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $this->assertViewer($request, $quiz);

        $attempt->delete();

        return response()->json(['message' => 'Attempt deleted.']);
    }

    private function assertViewer(Request $request, Quiz $quiz): void
    {
        $user = $request->user();

        if (! $user || (! $this->access->isStaff($user) && $quiz->created_by !== (string) $user->getKey())) {
            throw new QuizAccessDeniedException('You may not view results for this quiz.');
        }
    }
}
