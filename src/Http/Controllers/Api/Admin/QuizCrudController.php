<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Http\Requests\StoreQuizRequest;
use Osoobe\Quiz\Http\Resources\QuizResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Services\QuizAccess;

/**
 * index/store are gated staff-only by route middleware (config('quiz.route.admin_middleware')).
 * show/update/destroy are reachable by staff OR the quiz's own creator — a non-staff quiz
 * owner (e.g. an event organiser) must still manage their own quiz — so those three assert
 * ownership per-object instead of relying on the blanket admin middleware.
 */
class QuizCrudController
{
    public function __construct(private QuizAccess $access) {}

    public function index()
    {
        return QuizResource::collection(Quiz::query()->latest()->paginate(20));
    }

    public function store(StoreQuizRequest $request)
    {
        $quiz = Quiz::create($request->validated() + ['created_by' => $request->user()->getKey()]);

        return new QuizResource($quiz);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->assertManager($request, $quiz);

        return new QuizResource($quiz);
    }

    public function update(StoreQuizRequest $request, Quiz $quiz)
    {
        $this->assertManager($request, $quiz);

        $quiz->update($request->validated());

        return new QuizResource($quiz);
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        $this->assertManager($request, $quiz);

        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted.']);
    }

    private function assertManager(Request $request, Quiz $quiz): void
    {
        $user = $request->user();

        if (! $user || (! $this->access->isStaff($user) && $quiz->created_by !== (string) $user->getKey())) {
            throw new QuizAccessDeniedException('You may not manage this quiz.');
        }
    }
}
