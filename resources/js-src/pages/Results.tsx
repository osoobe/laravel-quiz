import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { QuizApi } from '../api/quiz';
import { ApiError } from '../api/client';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Avatar } from '../components/ui/Avatar';
import { ConfirmDelete } from '../components/ConfirmDelete';
import { EmptyState } from '../components/EmptyState';
import { Users } from 'lucide-react';
import type { ResultsAttempt } from '../api/types';

export function Results() {
    const { quizId } = useParams<{ quizId: string }>();
    const [attempts, setAttempts] = useState<ResultsAttempt[] | null>(null);

    function load() {
        if (!quizId) return;
        QuizApi.results(quizId)
            .then((res) => setAttempts(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load results');
                setAttempts([]);
            });
    }

    useEffect(load, [quizId]);

    return (
        <div className="mx-auto max-w-3xl px-4 py-10">
            <h1 className="text-2xl font-bold text-gray-900">Attempt Results</h1>

            <Card className="mt-6">
                {attempts === null ? (
                    <div className="flex justify-center py-10">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-quiz-primary border-t-transparent" />
                    </div>
                ) : attempts.length === 0 ? (
                    <EmptyState icon={<Users className="h-10 w-10" />} title="No attempts yet" />
                ) : (
                    <ul className="divide-y divide-quiz-border">
                        {attempts.map((attempt) => (
                            <li key={attempt.id} className="flex items-center gap-3 py-3">
                                <Avatar name={attempt.user.name} src={attempt.user.avatar_url} />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900">{attempt.user.name}</p>
                                    <p className="text-xs text-quiz-muted">
                                        Started {new Date(attempt.started_at).toLocaleString()}
                                        {attempt.status === 'completed' &&
                                            ` · ${attempt.correct_answers} correct / ${
                                                (attempt.total_questions ?? 0) - (attempt.correct_answers ?? 0)
                                            } wrong`}
                                    </p>
                                </div>
                                <StatusBadge attempt={attempt} />
                                {quizId && (
                                    <ConfirmDelete
                                        label="Delete attempt"
                                        description={`Remove ${attempt.user.name}'s attempt?`}
                                        onConfirm={async () => {
                                            await QuizApi.deleteAttempt(quizId, attempt.id);
                                            load();
                                        }}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </div>
    );
}

function StatusBadge({ attempt }: { attempt: ResultsAttempt }) {
    if (attempt.status === 'completed') return <Badge variant="solid">{attempt.score}%</Badge>;
    if (attempt.status === 'in_progress') return <Badge variant="secondary">In Progress</Badge>;

    return <Badge variant="outline">Abandoned</Badge>;
}
