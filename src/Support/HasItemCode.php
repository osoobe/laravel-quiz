<?php

namespace Osoobe\Quiz\Support;

/**
 * Auto-generates a unique itemcode on creation when one isn't explicitly given —
 * every quiz/question/topic/category ends up with one regardless of whether it's
 * created via the admin UI, an import, a factory, or a seeder.
 */
trait HasItemCode
{
    public static function bootHasItemCode(): void
    {
        static::creating(function ($model) {
            if (empty($model->itemcode)) {
                $model->itemcode = ItemCode::generateUnique(static::class);
            }
        });
    }
}
