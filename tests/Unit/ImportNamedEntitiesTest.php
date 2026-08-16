<?php

use Osoobe\Quiz\Actions\ImportCategories;
use Osoobe\Quiz\Actions\ImportTopics;
use Osoobe\Quiz\Models\QuizCategory;
use Osoobe\Quiz\Models\QuizTopic;

it('reports a failed row without aborting the rest of the topics import', function () {
    // Bypasses the Form Request (which requires a non-empty name) to exercise the
    // action's own per-row try/catch directly against a missing name.
    $summary = (new ImportTopics)->execute([
        ['name' => 'Valid Topic', 'description' => 'Fine.'],
        ['name' => null, 'description' => 'Missing name.'],
    ]);

    expect($summary['imported'])->toBe(1)->and($summary['failed'])->toBe(1);
    expect($summary['errors'])->toHaveCount(1);
    expect(QuizTopic::where('name', 'Valid Topic')->exists())->toBeTrue();
});

it('reports a failed row without aborting the rest of the categories import', function () {
    $summary = (new ImportCategories)->execute([
        ['name' => 'Valid Category', 'description' => 'Fine.'],
        ['name' => null, 'description' => 'Missing name.'],
    ]);

    expect($summary['imported'])->toBe(1)->and($summary['failed'])->toBe(1);
    expect(QuizCategory::where('name', 'Valid Category')->exists())->toBeTrue();
});
