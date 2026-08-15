<?php

namespace Osoobe\Quiz\Support;

use Illuminate\Support\Str;

class ItemCode
{
    /**
     * 6+ alphanumeric characters, optionally with dashes/underscores.
     */
    public const PATTERN = '/^[A-Za-z0-9_-]{6,}$/';

    public static function generateUnique(string $modelClass, int $length = 8): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while ($modelClass::where('itemcode', $code)->exists());

        return $code;
    }
}
