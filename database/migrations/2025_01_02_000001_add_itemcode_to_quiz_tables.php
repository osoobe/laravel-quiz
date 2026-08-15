<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A stable, human-assignable identifier (distinct from the UUID primary key) used to
 * match rows across import/export round-trips instead of relying on fuzzy name matching.
 * Nullable — existing rows predate this field, and the model auto-generates one on
 * create when it's left blank (see Support\HasItemCode), so it's never required input.
 */
return new class extends Migration
{
    private const TABLES = ['quiz_topics', 'quiz_categories', 'quiz_questions', 'quizzes'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('itemcode', 64)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['itemcode']);
                $blueprint->dropColumn('itemcode');
            });
        }
    }
};
