<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topic_id')->nullable()->constrained('quiz_topics')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('quiz_categories')->nullOnDelete();
            $table->text('question');
            $table->text('description')->nullable();
            $table->string('difficulty', 16)->default('medium');
            $table->string('question_type', 16)->default('radio');
            $table->json('answers');
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->timestamps();

            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
