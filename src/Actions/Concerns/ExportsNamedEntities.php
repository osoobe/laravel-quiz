<?php

namespace Osoobe\Quiz\Actions\Concerns;

use Illuminate\Support\Collection;

trait ExportsNamedEntities
{
    abstract protected function model(): string;

    public function execute(): Collection
    {
        $model = $this->model();

        return $model::query()->orderBy('name')->get()->map(fn ($entity) => [
            'itemcode' => $entity->itemcode,
            'name' => $entity->name,
            'description' => $entity->description,
            'is_active' => $entity->is_active,
        ]);
    }
}
