<?php

namespace Osoobe\Quiz\Http\Controllers\Api;

use Illuminate\Http\Request;
use Osoobe\Quiz\Actions\StartAttempt;
use Osoobe\Quiz\Actions\SubmitAttempt;
use Osoobe\Quiz\Exceptions\QuizAccessDeniedException;
use Osoobe\Quiz\Http\Requests\SubmitAttemptRequest;
use Osoobe\Quiz\Http\Resources\PublicQuestionResource;
use Osoobe\Quiz\Http\Resources\QuizResource;
use Osoobe\Quiz\Http\Resources\TakerAttemptResource;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizAttempt;
use Osoobe\Quiz\Models\QuizQuestion;

class AttemptController
{
    public function __construct(
        private StartAttempt $startAttempt,
        private SubmitAttempt $submitAttempt,
    ) {}

    public function store(Request $request, Quiz $quiz)
    {
        if (! $request->user()) {
            abort(401, 'You must be signed in to take a quiz.');
        }

        $attempt = $this->startAttempt->execute($request->user(), $quiz);

        return response()->json([
            'quiz' => new QuizResource($quiz),
            'attempt' => new TakerAttemptResource($attempt->setRelation('quiz', $quiz)),
            'questions' => $this->orderedQuestions($attempt),
        ]);
    }

    /**
     * Autosave — merges newly answered questions into the persisted answer map.
     * Nothing is graded here; scoring only happens on submit().
     */
    public function update(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $this->assertOwner($request, $attempt);

        $incoming = (array) $request->input('answers', []);

        $answers = collect($attempt->answers)->map(function ($row) use ($incoming) {
            if (array_key_exists($row['question_id'], $incoming)) {
                $row['selected_answers'] = array_values((array) $incoming[$row['question_id']]);
            }

            return $row;
        })->all();

        $attempt->update(['answers' => $answers]);

        return new TakerAttemptResource($attempt->setRelation('quiz', $quiz));
    }

    public function submit(SubmitAttemptRequest $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $this->assertOwner($request, $attempt);

        $attempt = $this->submitAttempt->execute($attempt, $request->input('answers', []));

        return response()->json([
            'score' => $attempt->score,
            'correct_answers' => $attempt->correct_answers,
            'total_questions' => $attempt->total_questions,
            'status' => $attempt->status->value,
        ]);
    }

    private function assertOwner(Request $request, QuizAttempt $attempt): void
    {
        if ($attempt->user_id !== (string) $request->user()?->getKey()) {
            throw new QuizAccessDeniedException('This is not your attempt.');
        }
    }

    private function orderedQuestions(QuizAttempt $attempt): array
    {
        $ids = collect($attempt->answers)->pluck('question_id');
        $questions = QuizQuestion::whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $questions->get($id))
            ->filter()
            ->map(fn ($question) => (new PublicQuestionResource($question))->resolve())
            ->values()
            ->all();
    }
}
