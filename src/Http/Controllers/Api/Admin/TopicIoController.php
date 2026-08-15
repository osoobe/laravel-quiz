<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Actions\ExportTopics;
use Osoobe\Quiz\Actions\ImportTopics;
use Osoobe\Quiz\Http\Requests\ImportTopicsRequest;

class TopicIoController
{
    public function export(ExportTopics $export)
    {
        return response()->json($export->execute());
    }

    public function import(ImportTopicsRequest $request, ImportTopics $import)
    {
        return response()->json($import->execute($request->validated()['topics']));
    }
}
