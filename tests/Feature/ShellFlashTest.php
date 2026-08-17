<?php

it('embeds the host session flash keys into window.QuizConfig for the SPA to surface', function () {
    $response = $this->withSession([
        'message' => 'Quiz created successfully',
        'error' => 'Something went wrong',
        'bulk_errors' => ['Row 2 failed', 'Row 5 failed'],
    ])->get('/quizzes');

    $response->assertOk();
    $response->assertSee('Quiz created successfully', false);
    $response->assertSee('Something went wrong', false);
    $response->assertSee('Row 2 failed', false);
});

it('renders a null flash payload when nothing was flashed to the session', function () {
    $response = $this->get('/quizzes');

    $response->assertOk();
    $response->assertSee('window.QuizConfig', false);
});
