<?php

namespace Osoobe\Quiz\Actions;

class ExportAllData
{
    public function __construct(
        private ExportTopics $topics,
        private ExportCategories $categories,
        private ExportQuestions $questions,
        private ExportQuizzes $quizzes,
    ) {
    }

    /**
     * @return array{topics: mixed, categories: mixed, questions: mixed, quizzes: mixed}
     */
    public function execute(): array
    {
        return [
            'topics' => $this->topics->execute(),
            'categories' => $this->categories->execute(),
            'questions' => $this->questions->execute(),
            'quizzes' => $this->quizzes->execute(),
        ];
    }
}
