<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Osoobe\Quiz\Models\Quiz;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizQuestion;
use Osoobe\Quiz\Models\QuizTopic;
use Workbench\App\Models\User;

beforeEach(function () {
    Gate::define('quiz.staff', fn () => true);
});

it('exports questions as a flat array with resolved topic/category names', function () {
    $topic = QuizTopic::factory()->create(['name' => 'Databases']);
    QuizQuestion::factory()->create(['topic_id' => $topic->id, 'question' => 'What is a primary key?']);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/questions-export')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.topic'))->toBe('Databases');
    expect($response->json('0.question'))->toBe('What is a primary key?');
});

it('imports questions, resolving topics by name and reporting a summary', function () {
    QuizTopic::factory()->create(['name' => 'Networking']);

    $payload = [
        'questions' => [
            [
                'question' => 'What does DNS stand for?',
                'topic' => 'networking', // case-insensitive match
                'answers' => [
                    ['text' => 'Domain Name System', 'is_correct' => true],
                    ['text' => 'Digital Network Service', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/questions-import', $payload)
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(QuizQuestion::first()->topic->name)->toBe('Networking');
});

it('exports categories as a flat name/description/is_active array', function () {
    QuizCategory::factory()->create(['name' => 'Algorithms', 'description' => 'Step-by-step procedures.']);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/categories-export')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.name'))->toBe('Algorithms');
    expect($response->json('0.description'))->toBe('Step-by-step procedures.');
});

it('imports categories, creating new ones and reporting a summary', function () {
    $payload = [
        'categories' => [
            ['name' => 'Cloud Computing', 'description' => 'On-demand computing resources.'],
            ['name' => 'Cryptography', 'description' => 'Techniques for securing information.'],
        ],
    ];

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/categories-import', $payload)
        ->assertOk();

    expect($response->json('imported'))->toBe(2)->and($response->json('failed'))->toBe(0);
    expect(QuizCategory::where('name', 'Cloud Computing')->exists())->toBeTrue();
});

it('re-importing categories updates existing rows instead of failing on the unique name', function () {
    QuizCategory::factory()->create(['name' => 'Algorithms', 'description' => 'Old description.']);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/categories-import', [
            'categories' => [['name' => 'Algorithms', 'description' => 'New description.']],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(QuizCategory::count())->toBe(1);
    expect(QuizCategory::first()->description)->toBe('New description.');
});

it('blocks a non-staff user from importing or exporting categories', function () {
    Gate::define('quiz.staff', fn () => false);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/categories-export')
        ->assertStatus(403);
});

it('exports topics as a flat name/description/is_active array', function () {
    QuizTopic::factory()->create(['name' => 'Recursion', 'description' => 'Functions that call themselves.']);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/topics-export')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.name'))->toBe('Recursion');
});

it('imports topics, creating new ones and reporting a summary', function () {
    $payload = [
        'topics' => [
            ['name' => 'Arrays', 'description' => 'Contiguous, indexable storage.'],
            ['name' => 'Backtracking', 'description' => 'Trial-and-error search with undo.'],
        ],
    ];

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics-import', $payload)
        ->assertOk();

    expect($response->json('imported'))->toBe(2)->and($response->json('failed'))->toBe(0);
    expect(QuizTopic::where('name', 'Arrays')->exists())->toBeTrue();
});

it('re-importing topics updates existing rows instead of failing on the unique name', function () {
    QuizTopic::factory()->create(['name' => 'Arrays', 'description' => 'Old description.']);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics-import', [
            'topics' => [['name' => 'Arrays', 'description' => 'New description.']],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(QuizTopic::count())->toBe(1);
    expect(QuizTopic::first()->description)->toBe('New description.');
});

it('blocks a non-staff user from importing or exporting topics', function () {
    Gate::define('quiz.staff', fn () => false);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/topics-export')
        ->assertStatus(403);
});

it('matches an existing topic by itemcode even when the name has changed, taking priority over name matching', function () {
    $topic = QuizTopic::factory()->create(['name' => 'Old Name', 'itemcode' => 'STABLE-01']);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/topics-import', [
            'topics' => [['itemcode' => 'STABLE-01', 'name' => 'New Name', 'description' => 'Updated via itemcode.']],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(QuizTopic::count())->toBe(1);
    expect($topic->fresh()->name)->toBe('New Name');
});

it('exports and re-imports a category by itemcode as a stable round trip', function () {
    QuizCategory::factory()->create(['itemcode' => 'CAT-STAB1', 'name' => 'Networking', 'description' => 'Original.']);

    $exported = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/categories-export')
        ->assertOk()
        ->json();

    expect($exported[0]['itemcode'])->toBe('CAT-STAB1');

    // Simulate a real round trip: rename the row externally, then re-import the export.
    $exported[0]['name'] = 'Networking Fundamentals';

    $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/categories-import', ['categories' => $exported])
        ->assertOk()
        ->assertJsonPath('imported', 1)
        ->assertJsonPath('failed', 0);

    expect(QuizCategory::count())->toBe(1);
    expect(QuizCategory::first()->name)->toBe('Networking Fundamentals');
});

it('matches an existing question by itemcode and preserves its original creator on update', function () {
    $originalCreator = (string) Str::uuid();
    $question = QuizQuestion::factory()->create(['itemcode' => 'Q-STABLE1', 'created_by' => $originalCreator]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/questions-import', [
            'questions' => [[
                'itemcode' => 'Q-STABLE1',
                'question' => 'Updated question text',
                'answers' => [
                    ['text' => 'a', 'is_correct' => true],
                    ['text' => 'b', 'is_correct' => false],
                ],
            ]],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(QuizQuestion::count())->toBe(1);
    $question->refresh();
    expect($question->question)->toBe('Updated question text');
    expect($question->created_by)->toBe($originalCreator);
});

it('exports quizzes with topic_ids/category_ids resolved to names', function () {
    $topic = QuizTopic::factory()->create(['name' => 'Graphs']);
    $category = QuizCategory::factory()->create(['name' => 'Algorithms']);
    Quiz::factory()->create(['name' => 'Graph Theory Quiz', 'topic_ids' => [$topic->id], 'category_ids' => [$category->id]]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/quizzes-export')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.name'))->toBe('Graph Theory Quiz');
    expect($response->json('0.topics'))->toBe(['Graphs']);
    expect($response->json('0.categories'))->toBe(['Algorithms']);
});

it('imports quizzes, resolving topic/category names back to ids', function () {
    $topic = QuizTopic::factory()->create(['name' => 'Recursion']);
    $category = QuizCategory::factory()->create(['name' => 'Data Structures']);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/quizzes-import', [
            'quizzes' => [[
                'name' => 'New Quiz',
                'topics' => ['recursion'], // case-insensitive match
                'categories' => ['Data Structures'],
                'question_count' => 5,
                'max_attempts' => 2,
                'audience' => 'everyone',
            ]],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);

    $quiz = Quiz::first();
    expect($quiz->topic_ids)->toBe([$topic->id]);
    expect($quiz->category_ids)->toBe([$category->id]);
    expect($quiz->max_attempts)->toBe(2);
});

it('re-imports a quiz matched by itemcode, updating rather than duplicating', function () {
    $quiz = Quiz::factory()->create(['itemcode' => 'QUIZ-STAB1', 'name' => 'Old Quiz Name']);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/quiz/admin/quizzes-import', [
            'quizzes' => [['itemcode' => 'QUIZ-STAB1', 'name' => 'Renamed Quiz']],
        ])
        ->assertOk();

    expect($response->json('imported'))->toBe(1)->and($response->json('failed'))->toBe(0);
    expect(Quiz::count())->toBe(1);
    expect($quiz->fresh()->name)->toBe('Renamed Quiz');
});

it('blocks a non-staff user from importing or exporting quizzes', function () {
    Gate::define('quiz.staff', fn () => false);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/quiz/admin/quizzes-export')
        ->assertStatus(403);
});
