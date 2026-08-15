<?php

namespace Osoobe\Quiz\Actions;

use InvalidArgumentException;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizTopic;
use Throwable;

/**
 * Expects the same shape ExportQuizzes produces — topics/categories referenced by
 * name (not raw UUID), resolved back to topic_ids/category_ids here. Re-importing is
 * idempotent: an existing quiz is matched first by itemcode, falling back to name.
 */
class ImportQuizzes
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{imported: int, failed: int, errors: array<int, array{row: int, message: string}>}
     */
    public function execute(array $rows, string $createdBy): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    throw new InvalidArgumentException('A name is required.');
                }

                $itemcode = trim((string) ($row['itemcode'] ?? ''));
                $existing = $itemcode !== '' ? Quiz::where('itemcode', $itemcode)->first() : null;
                $existing ??= Quiz::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                $attributes = [
                    'name' => $name,
                    'description' => $row['description'] ?? null,
                    'topic_ids' => $this->resolveIds(QuizTopic::class, (array) ($row['topics'] ?? [])),
                    'category_ids' => $this->resolveIds(QuizCategory::class, (array) ($row['categories'] ?? [])),
                    'difficulty' => $row['difficulty'] ?? null,
                    'question_count' => $row['question_count'] ?? 10,
                    'randomize_questions' => $row['randomize_questions'] ?? true,
                    'time_limit_minutes' => $row['time_limit_minutes'] ?? null,
                    'max_attempts' => $row['max_attempts'] ?? 1,
                    'is_active' => $row['is_active'] ?? true,
                    'audience' => $row['audience'] ?? 'everyone',
                ];

                if ($itemcode !== '') {
                    $attributes['itemcode'] = $itemcode;
                }

                if ($existing) {
                    // created_by is preserved from the original creator, not overwritten.
                    $existing->update($attributes);
                } else {
                    Quiz::create($attributes + ['created_by' => $createdBy]);
                }

                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index, 'message' => $e->getMessage()];
            }
        }

        return compact('imported', 'failed', 'errors');
    }

    /**
     * @param  array<int, mixed>  $names
     * @return array<int, string>
     */
    private function resolveIds(string $model, array $names): array
    {
        return collect($names)
            ->map(fn ($name) => $model::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $name))])->value('id'))
            ->filter()
            ->values()
            ->all();
    }
}
