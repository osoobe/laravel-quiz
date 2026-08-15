<?php

namespace Osoobe\Quiz\Actions;

use Illuminate\Support\Str;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Throwable;

class ImportQuestions
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
                $itemcode = trim((string) ($row['itemcode'] ?? ''));
                $existing = $itemcode !== '' ? QuizQuestion::where('itemcode', $itemcode)->first() : null;

                $attributes = [
                    'topic_id' => $this->resolveId(QuizTopic::class, $row['topic_id'] ?? null, $row['topic'] ?? null),
                    'category_id' => $this->resolveId(QuizCategory::class, $row['category_id'] ?? null, $row['category'] ?? null),
                    'question' => $row['question'],
                    'description' => $row['description'] ?? null,
                    'difficulty' => $row['difficulty'] ?? 'medium',
                    'question_type' => $row['question_type'] ?? 'radio',
                    'answers' => $row['answers'],
                ];

                if ($itemcode !== '') {
                    $attributes['itemcode'] = $itemcode;
                }

                if ($existing) {
                    // created_by is preserved from the original creator, not overwritten.
                    $existing->update($attributes);
                } else {
                    QuizQuestion::create($attributes + ['created_by' => $createdBy]);
                }

                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index, 'message' => $e->getMessage()];
            }
        }

        return compact('imported', 'failed', 'errors');
    }

    private function resolveId(string $model, ?string $id, ?string $name): ?string
    {
        $value = $id ?: $name;

        if (! $value) {
            return null;
        }

        if (Str::isUuid($value)) {
            return $value;
        }

        return $model::query()->whereRaw('LOWER(name) = ?', [Str::lower($value)])->value('id');
    }
}
