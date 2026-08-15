import { useEffect, useState } from 'react';
import { FileQuestion } from 'lucide-react';
import { toast } from 'sonner';
import { QuizApi } from '../api/quiz';
import { ApiError } from '../api/client';
import { QuizCard } from '../components/QuizCard';
import { EmptyState } from '../components/EmptyState';
import type { CatalogueQuiz } from '../api/types';

export function Catalogue() {
    const [quizzes, setQuizzes] = useState<CatalogueQuiz[] | null>(null);
    const user = window.QuizConfig.user;

    useEffect(() => {
        QuizApi.catalogue()
            .then((res) => setQuizzes(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load quizzes');
                setQuizzes([]);
            });
    }, []);

    return (
        <div className="mx-auto max-w-5xl px-4 py-10">
            <h1 className="text-3xl font-bold text-gray-900">Technical Quizzes</h1>
            <p className="mt-1 text-quiz-muted">Test your knowledge and climb the leaderboard.</p>

            <div className="mt-8">
                {quizzes === null ? (
                    <div className="flex justify-center py-16">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-quiz-primary border-t-transparent" />
                    </div>
                ) : quizzes.length === 0 ? (
                    <EmptyState icon={<FileQuestion className="h-10 w-10" />} title="No quizzes available" />
                ) : (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {quizzes.map((quiz) => (
                            <QuizCard key={quiz.id} quiz={quiz} isAuthenticated={!!user} loginUrl={window.QuizConfig.loginUrl} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
