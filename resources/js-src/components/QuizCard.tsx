import { Link } from 'react-router-dom';
import { Clock, FileQuestion, Play, Trophy } from 'lucide-react';
import { Card } from './ui/Card';
import { Badge } from './ui/Badge';
import { DifficultyBadge } from './DifficultyBadge';
import type { CatalogueQuiz } from '../api/types';

export function QuizCard({ quiz, isAuthenticated, loginUrl }: { quiz: CatalogueQuiz; isAuthenticated: boolean; loginUrl: string | null }) {
    return (
        <Card className="flex flex-col gap-3">
            <div>
                <h3 className="text-lg font-semibold text-gray-900">{quiz.name}</h3>
                {quiz.description && <p className="mt-1 line-clamp-2 text-sm text-quiz-muted">{quiz.description}</p>}
            </div>

            <div className="flex flex-wrap gap-1.5">
                <Badge variant="outline">
                    <FileQuestion className="h-3 w-3" aria-hidden />
                    {quiz.question_count} questions
                </Badge>
                {quiz.time_limit_minutes && (
                    <Badge variant="outline">
                        <Clock className="h-3 w-3" aria-hidden />
                        {quiz.time_limit_minutes} min
                    </Badge>
                )}
                <DifficultyBadge difficulty={quiz.difficulty} />
                {quiz.topic && <Badge variant="outline">{quiz.topic}</Badge>}
            </div>

            <div className="mt-auto flex gap-2 pt-2">
                {isAuthenticated ? (
                    <Link
                        to={`/${quiz.id}`}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-quiz-primary px-3 py-2 text-sm font-medium text-quiz-primary-foreground hover:opacity-90"
                    >
                        <Play className="h-4 w-4" aria-hidden />
                        Start Quiz
                    </Link>
                ) : (
                    <a
                        href={loginUrl ?? '#'}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-quiz-border px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
                    >
                        Sign in to take quiz
                    </a>
                )}
                <Link
                    to={`/${quiz.id}/leaderboard`}
                    className="inline-flex items-center gap-1.5 rounded-lg border border-quiz-border px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
                >
                    <Trophy className="h-4 w-4" aria-hidden />
                    Leaderboard
                </Link>
            </div>
        </Card>
    );
}
