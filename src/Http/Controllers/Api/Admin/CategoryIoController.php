<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Actions\ExportCategories;
use Osoobe\Quiz\Actions\ImportCategories;
use Osoobe\Quiz\Http\Requests\ImportCategoriesRequest;

class CategoryIoController
{
    public function export(ExportCategories $export)
    {
        return response()->json($export->execute());
    }

    public function import(ImportCategoriesRequest $request, ImportCategories $import)
    {
        return response()->json($import->execute($request->validated()['categories']));
    }
}
