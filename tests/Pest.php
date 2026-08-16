<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Osoobe\Quiz\Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * base_path() resolves to Testbench's own internal skeleton app directory, not this
 * package — use this instead whenever a test needs a real path inside the package
 * itself (its own vendor/, or tests/Fixtures/).
 */
function packagePath(string $path = ''): string
{
    return realpath(__DIR__.'/..').($path !== '' ? '/'.ltrim($path, '/\\') : '');
}
