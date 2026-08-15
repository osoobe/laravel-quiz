<?php

namespace Osoobe\Quiz\Actions\Concerns;

use InvalidArgumentException;
use Throwable;

/**
 * Shared by ImportTopics and ImportCategories — both quiz_topics and quiz_categories
 * are name/description/is_active with a unique `name` (and itemcode). Re-importing is
 * idempotent: an existing row is matched first by itemcode (if the row provides one —
 * the stable identifier this is meant for), falling back to name matching
 * (case-insensitively) so older exports without an itemcode still round-trip. A match
 * either way updates the row rather than colliding with the unique constraints; rows
 * that don't provide an itemcode get one auto-generated on create (see HasItemCode).
 */
trait ImportsNamedEntities
{
    abstract protected function model(): string;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{imported: int, failed: int, errors: array<int, array{row: int, message: string}>}
     */
    public function execute(array $rows): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];
        $model = $this->model();

        foreach ($rows as $index => $row) {
            try {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    throw new InvalidArgumentException('A name is required.');
                }

                $itemcode = trim((string) ($row['itemcode'] ?? ''));
                $existing = $itemcode !== '' ? $model::where('itemcode', $itemcode)->first() : null;
                $existing ??= $model::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                $attributes = [
                    'name' => $name,
                    'description' => $row['description'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                ];

                if ($itemcode !== '') {
                    $attributes['itemcode'] = $itemcode;
                }

                if ($existing) {
                    $existing->update($attributes);
                } else {
                    $model::create($attributes);
                }

                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index, 'message' => $e->getMessage()];
            }
        }

        return compact('imported', 'failed', 'errors');
    }
}
