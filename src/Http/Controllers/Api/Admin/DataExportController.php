<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Actions\ExportAllData;

class DataExportController
{
    public function exportAll(ExportAllData $export)
    {
        return response()->json($export->execute());
    }
}
