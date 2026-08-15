<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->foreignUuid('topic_id')->nullable()->constrained('quiz_topics')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('quiz_categories')->nullOnDelete();
            $table->json('topic_ids');
            $table->json('category_ids');
            $table->string('difficulty', 16)->nullable();
            $table->unsignedInteger('question_count')->default(10);
            $table->boolean('randomize_questions')->default(true);
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('audience', 64)->default('everyone');
            $table->uuid('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
