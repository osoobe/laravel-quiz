<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Osoobe\Quiz\Tests\TestCase;
use Workbench\App\Models\User;

beforeEach(function () {
    Gate::define('quiz.staff', fn () => true);
});

/**
 * Mirrors exactly what the admin UI's chunked importer does: split the real sample
 * file into fixed-size batches and POST them sequentially, accumulating the summary.
 */
function importInChunks(TestCase $test, string $endpoint, string $key, array $rows, int $chunkSize = 25): array
{
    $imported = 0;
    $failed = 0;

    foreach (array_chunk($rows, $chunkSize) as $chunk) {
        $response = $test->postJson($endpoint, [$key => $chunk])->assertOk();
        $imported += $response->json('imported');
        $failed += $response->json('failed');
    }

    return ['imported' => $imported, 'failed' => $failed];
}

it('imports the real sample topics, categories, and questions files end to end via chunked requests', function () {
    $this->actingAs(User::factory()->create());

    $topics = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz_topics.json')), true);
    $categories = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz_categories.json')), true);
    $questions = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz-questions.json')), true);

    $topicSummary = importInChunks($this, '/api/quiz/admin/topics-import', 'topics', $topics);
    expect($topicSummary['failed'])->toBe(0);
    expect(QuizTopic::count())->toBe(count($topics));

    $categorySummary = importInChunks($this, '/api/quiz/admin/categories-import', 'categories', $categories);
    expect($categorySummary['failed'])->toBe(0);
    expect(QuizCategory::count())->toBe(count($categories));

    $questionSummary = importInChunks($this, '/api/quiz/admin/questions-import', 'questions', $questions);
    expect($questionSummary['failed'])->toBe(0);
    expect(QuizQuestion::count())->toBe(count($questions));

    // Spot-check that name resolution actually linked real rows, not just inserted orphans.
    $linked = QuizQuestion::whereNotNull('topic_id')->orWhereNotNull('category_id')->count();
    expect($linked)->toBe(count($questions));
});
