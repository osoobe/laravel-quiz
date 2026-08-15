<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Actions\ExportQuizzes;
use Osoobe\Quiz\Actions\ImportQuizzes;
use Osoobe\Quiz\Http\Requests\ImportQuizzesRequest;

class QuizIoController
{
    public function export(ExportQuizzes $export)
    {
        return response()->json($export->execute());
    }

    public function import(ImportQuizzesRequest $request, ImportQuizzes $import)
    {
        return response()->json($import->execute($request->validated()['quizzes'], $request->user()->getKey()));
    }
}
