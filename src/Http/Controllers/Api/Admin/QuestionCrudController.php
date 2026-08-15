<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Osoobe\Quiz\Http\Requests\StoreQuestionRequest;
use Osoobe\Quiz\Http\Resources\QuestionResource;
use Osoobe\Quiz\Models\QuizQuestion;

class QuestionCrudController
{
    public function index(Request $request)
    {
        $query = QuizQuestion::query()->with(['topic', 'category'])->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where('question', 'like', "%{$search}%");
        }

        if ($difficulty = $request->query('difficulty')) {
            $query->where('difficulty', $difficulty);
        }

        return QuestionResource::collection($query->paginate(20));
    }

    public function store(StoreQuestionRequest $request)
    {
        $question = QuizQuestion::create($request->validated() + ['created_by' => $request->user()->getKey()]);

        return new QuestionResource($question->load(['topic', 'category']));
    }

    public function show(QuizQuestion $question)
    {
        return new QuestionResource($question->load(['topic', 'category']));
    }

    public function update(StoreQuestionRequest $request, QuizQuestion $question)
    {
        $question->update($request->validated());

        return new QuestionResource($question->load(['topic', 'category']));
    }

    public function destroy(QuizQuestion $question)
    {
        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }
}
