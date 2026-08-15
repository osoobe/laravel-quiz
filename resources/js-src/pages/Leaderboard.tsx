import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Trophy } from 'lucide-react';
import { toast } from 'sonner';
import { QuizApi } from '../api/quiz';
import { ApiError } from '../api/client';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Avatar } from '../components/ui/Avatar';
import { Button } from '../components/ui/Button';
import { RankIcon } from '../components/RankIcon';
import { EmptyState } from '../components/EmptyState';
import { cn } from '../lib/cn';
import type { LeaderboardEntry } from '../api/types';

export function Leaderboard() {
    const { quizId } = useParams<{ quizId: string }>();
    const navigate = useNavigate();
    const [name, setName] = useState('');
    const [entries, setEntries] = useState<LeaderboardEntry[] | null>(null);

    useEffect(() => {
        if (!quizId) return;
        QuizApi.leaderboard(quizId)
            .then((res) => {
                setName(res.quiz.name);
                setEntries(res.entries);
            })
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load the leaderboard');
                setEntries([]);
            });
    }, [quizId]);

    return (
        <div className="mx-auto max-w-2xl px-4 py-10">
            <Link to="/" className="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900">
                <ArrowLeft className="h-4 w-4" aria-hidden /> Back to Quizzes
            </Link>

            <h1 className="mt-4 flex items-center gap-2 text-2xl font-bold text-gray-900">
                <Trophy className="h-6 w-6 text-quiz-primary" aria-hidden />
                Leaderboard
            </h1>
            <p className="text-sm text-quiz-muted">{name}</p>

            <Card className="mt-6">
                <h2 className="mb-4 font-semibold text-gray-900">Top Performers</h2>

                {entries === null ? (
                    <div className="flex justify-center py-10">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-quiz-primary border-t-transparent" />
                    </div>
                ) : entries.length === 0 ? (
                    <EmptyState
                        icon={<Trophy className="h-10 w-10" />}
                        title="No attempts yet. Be the first!"
                        action={
                            quizId && (
                                <Button onClick={() => navigate(`/${quizId}`)} className="mt-2">
                                    Take Quiz
                                </Button>
                            )
                        }
                    />
                ) : (
                    <ul className="space-y-2">
                        {entries.map((entry, index) => (
                            <li
                                key={index}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2.5',
                                    index < 3 && 'bg-quiz-accent',
                                )}
                            >
                                <RankIcon rank={index + 1} />
                                <Avatar name={entry.user.name} src={entry.user.avatar_url} />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900">{entry.user.name}</p>
                                    <p className="text-xs text-quiz-muted">
                                        {entry.correct_answers}/{entry.total_questions} correct
                                    </p>
                                </div>
                                <Badge variant={index < 3 ? 'solid' : 'secondary'}>{entry.score}%</Badge>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </div>
    );
}
