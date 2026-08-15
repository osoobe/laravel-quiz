<?php

namespace Osoobe\Quiz\Http\Controllers\Api;

use Illuminate\Http\Request;
use Osoobe\Quiz\Http\Resources\QuizCatalogueResource;
use Osoobe\Quiz\Models\Quiz;

class CatalogueController
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()
            ->with('topic')
            ->catalogue()
            ->visibleTo($request->user())
            ->latest()
            ->paginate(20);

        return QuizCatalogueResource::collection($quizzes);
    }
}
