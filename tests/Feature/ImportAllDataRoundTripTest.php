<?php

use Illuminate\Support\Facades\Gate;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Osoobe\Quiz\Tests\TestCase;
use Workbench\App\Models\User;

beforeEach(function () {
    Gate::define('quiz.staff', fn () => true);
});

/**
 * Mirrors exactly what the "Import All Data" button does: export the bundle, then
 * replay each non-empty section through its own import endpoint in dependency
 * order (topics/categories before questions/quizzes, since those reference the
 * former by name).
 */
function importBundleInPhases(TestCase $test, array $bundle): array
{
    $endpoints = [
        'topics' => '/api/quiz/admin/topics-import',
        'categories' => '/api/quiz/admin/categories-import',
        'questions' => '/api/quiz/admin/questions-import',
        'quizzes' => '/api/quiz/admin/quizzes-import',
    ];

    $totals = [];

    foreach ($endpoints as $key => $endpoint) {
        $rows = $bundle[$key] ?? [];

        if (empty($rows)) {
            continue;
        }

        foreach (array_chunk($rows, 25) as $chunk) {
            $response = $test->postJson($endpoint, [$key => $chunk])->assertOk();
            $totals[$key]['imported'] = ($totals[$key]['imported'] ?? 0) + $response->json('imported');
            $totals[$key]['failed'] = ($totals[$key]['failed'] ?? 0) + $response->json('failed');
        }
    }

    return $totals;
}

it('round-trips export-all straight back through import-all with zero failures and no duplicates', function () {
    $this->actingAs(User::factory()->create());

    $topic = QuizTopic::factory()->create(['name' => 'Trees']);
    $category = QuizCategory::factory()->create(['name' => 'Data Structures']);
    QuizQuestion::factory()->create(['topic_id' => $topic->id, 'category_id' => $category->id]);
    Quiz::factory()->create(['name' => 'Trees Quiz', 'topic_ids' => [$topic->id], 'category_ids' => [$category->id]]);

    $bundle = $this->getJson('/api/quiz/admin/export-all')->assertOk()->json();

    $totals = importBundleInPhases($this, $bundle);

    foreach (['topics', 'categories', 'questions', 'quizzes'] as $key) {
        expect($totals[$key]['failed'])->toBe(0);
    }

    // Re-importing the exact export must match every row by itemcode and update in
    // place — counts must stay exactly as they were, nothing duplicated.
    expect(QuizTopic::count())->toBe(1);
    expect(QuizCategory::count())->toBe(1);
    expect(QuizQuestion::count())->toBe(1);
    expect(Quiz::count())->toBe(1);
});

it('imports the real sample files followed by a quizzes-only bundle without needing questions or categories re-sent', function () {
    $this->actingAs(User::factory()->create());

    $topics = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz_topics.json')), true);
    $categories = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz_categories.json')), true);
    $questions = json_decode(file_get_contents(packagePath('tests/Fixtures/quizdata/quiz-questions.json')), true);

    importBundleInPhases($this, ['topics' => $topics, 'categories' => $categories, 'questions' => $questions]);

    expect(QuizTopic::count())->toBe(count($topics));
    expect(QuizCategory::count())->toBe(count($categories));
    expect(QuizQuestion::count())->toBe(count($questions));

    // A partial bundle (quizzes only) must not touch topics/categories/questions at all.
    $totals = importBundleInPhases($this, [
        'quizzes' => [['name' => 'Full Stack Assessment', 'topics' => ['Arrays'], 'question_count' => 10]],
    ]);

    expect($totals['quizzes']['failed'])->toBe(0);
    expect(Quiz::count())->toBe(1);
    expect(QuizTopic::count())->toBe(count($topics));
    expect(QuizQuestion::count())->toBe(count($questions));
});
