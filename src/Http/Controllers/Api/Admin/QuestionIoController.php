<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Actions\ExportQuestions;
use Osoobe\Quiz\Actions\ImportQuestions;
use Osoobe\Quiz\Http\Requests\ImportQuestionsRequest;

class QuestionIoController
{
    public function export(ExportQuestions $export)
    {
        return response()->json($export->execute());
    }

    public function import(ImportQuestionsRequest $request, ImportQuestions $import)
    {
        $summary = $import->execute($request->validated()['questions'], $request->user()->getKey());

        return response()->json($summary);
    }
}
