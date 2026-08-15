import { type ReactNode, useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { CheckCircle, ChevronLeft, ChevronRight, Lock, Mail } from 'lucide-react';
import { ApiError } from '../api/client';
import { QuizApi } from '../api/quiz';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Progress } from '../components/ui/Progress';
import { CountdownBadge } from '../components/CountdownBadge';
import { QuestionDots } from '../components/QuestionDots';
import { cn } from '../lib/cn';
import type { Question, TakerPayload } from '../api/types';

type ViewState =
    | { kind: 'loading' }
    | { kind: 'access_denied'; message: string }
    | { kind: 'max_attempts_reached'; message: string }
    | { kind: 'taking'; payload: TakerPayload }
    | { kind: 'completed'; score: number; correct: number; total: number };

export function Taker() {
    const { quizId } = useParams<{ quizId: string }>();
    const navigate = useNavigate();
    const [state, setState] = useState<ViewState>({ kind: 'loading' });
    const [current, setCurrent] = useState(0);
    const [answersMap, setAnswersMap] = useState<Record<string, string[]>>({});
    const saveTimer = useRef<number | undefined>(undefined);

    useEffect(() => {
        if (!window.QuizConfig.user) {
            window.location.href = window.QuizConfig.loginUrl ?? '/';

            return;
        }

        if (!quizId) return;

        QuizApi.startAttempt(quizId)
            .then((payload) => {
                setState({ kind: 'taking', payload });
                setAnswersMap(payload.attempt.answers);
            })
            .catch((error: ApiError) => {
                if (error.errorCode === 'quiz.max_attempts_reached') {
                    setState({ kind: 'max_attempts_reached', message: error.message });
                } else {
                    setState({ kind: 'access_denied', message: error.message });
                }
            });
    }, [quizId]);

    const autosave = useCallback(
        (attemptId: string, next: Record<string, string[]>) => {
            if (!quizId) return;
            window.clearTimeout(saveTimer.current);
            saveTimer.current = window.setTimeout(() => {
                QuizApi.autosave(quizId, attemptId, next).catch(() => undefined);
            }, 500);
        },
        [quizId],
    );

    function setAnswer(question: Question, answerId: string) {
        if (state.kind !== 'taking') return;
        const attemptId = state.payload.attempt.id;

        setAnswersMap((prev) => {
            const existing = prev[question.id] ?? [];
            const next =
                question.question_type === 'radio'
                    ? [answerId]
                    : existing.includes(answerId)
                      ? existing.filter((id) => id !== answerId)
                      : [...existing, answerId];

            const merged = { ...prev, [question.id]: next };
            autosave(attemptId, merged);

            return merged;
        });
    }

    async function submit() {
        if (state.kind !== 'taking' || !quizId) return;
        const result = await QuizApi.submit(quizId, state.payload.attempt.id, answersMap);
        setState({ kind: 'completed', score: result.score, correct: result.correct_answers, total: result.total_questions });
    }

    if (state.kind === 'loading') {
        return (
            <div className="flex min-h-[50vh] items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-2 border-quiz-primary border-t-transparent" />
            </div>
        );
    }

    if (state.kind === 'access_denied') {
        return (
            <StateCard icon={<Lock className="h-8 w-8" />} title="Access Denied">
                <p className="text-sm text-quiz-muted">{state.message}</p>
                <a
                    href={`mailto:?subject=Quiz access request&body=${encodeURIComponent(window.location.href)}`}
                    className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-quiz-primary hover:underline"
                >
                    <Mail className="h-4 w-4" aria-hidden /> Contact the organiser
                </a>
            </StateCard>
        );
    }

    if (state.kind === 'max_attempts_reached') {
        return (
            <StateCard icon={<Lock className="h-8 w-8" />} title="Attempt limit reached">
                <p className="text-sm text-quiz-muted">{state.message}</p>
                <div className="mt-4 flex justify-center gap-2">
                    <Button variant="outline" onClick={() => navigate('/')}>
                        Back to Quizzes
                    </Button>
                    <Button onClick={() => navigate(`/${quizId}/leaderboard`)}>View Leaderboard</Button>
                </div>
            </StateCard>
        );
    }

    if (state.kind === 'completed') {
        return (
            <StateCard icon={<CheckCircle className="h-10 w-10" />} title="Quiz Completed!">
                <p className="text-4xl font-bold text-quiz-primary">{state.score}%</p>
                <p className="mt-2 text-sm text-quiz-muted">
                    You got {state.correct} out of {state.total} questions correct.
                </p>
                <div className="mt-6 flex justify-center gap-2">
                    <Button variant="outline" onClick={() => navigate('/')}>
                        Back to Quizzes
                    </Button>
                    <Button onClick={() => navigate(`/${quizId}/leaderboard`)}>View Leaderboard</Button>
                </div>
            </StateCard>
        );
    }

    const { quiz, questions } = state.payload;
    const question = questions[current];
    const answered = questions.map((q) => (answersMap[q.id] ?? []).length > 0);

    return (
        <div className="mx-auto max-w-2xl px-4 py-10">
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">{quiz.name}</h1>
                    <p className="text-sm text-quiz-muted">
                        Question {current + 1} of {questions.length}
                    </p>
                </div>
                {state.payload.attempt.expires_at && (
                    <CountdownBadge expiresAt={state.payload.attempt.expires_at} onExpire={submit} />
                )}
            </div>

            <Progress value={((current + 1) / questions.length) * 100} />

            <Card className="mt-4">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="font-semibold text-gray-900">{question.question}</p>
                        {question.description && <p className="mt-1 text-sm text-quiz-muted">{question.description}</p>}
                    </div>
                    <Badge variant="outline">{question.question_type === 'radio' ? 'Single' : 'Multiple'}</Badge>
                </div>

                <div
                    className="mt-4 space-y-2"
                    role={question.question_type === 'radio' ? 'radiogroup' : 'group'}
                    aria-label={question.question}
                >
                    {question.answers.map((answer) => {
                        const selected = (answersMap[question.id] ?? []).includes(answer.id);

                        return (
                            <button
                                key={answer.id}
                                type="button"
                                role={question.question_type === 'radio' ? 'radio' : 'checkbox'}
                                aria-checked={selected}
                                onClick={() => setAnswer(question, answer.id)}
                                className={cn(
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm transition-colors',
                                    selected ? 'border-quiz-primary bg-quiz-accent' : 'border-quiz-border hover:bg-gray-50',
                                )}
                            >
                                <span
                                    className={cn(
                                        'h-4 w-4 shrink-0 border',
                                        question.question_type === 'radio' ? 'rounded-full' : 'rounded',
                                        selected ? 'border-quiz-primary bg-quiz-primary' : 'border-gray-300',
                                    )}
                                />
                                {answer.text}
                            </button>
                        );
                    })}
                </div>
            </Card>

            <div className="mt-4 flex items-center justify-between">
                <Button variant="outline" disabled={current === 0} onClick={() => setCurrent((c) => c - 1)}>
                    <ChevronLeft className="h-4 w-4" aria-hidden /> Previous
                </Button>
                <QuestionDots count={questions.length} current={current} answered={answered} onJump={setCurrent} />
                {current === questions.length - 1 ? (
                    <Button onClick={submit}>Submit Quiz</Button>
                ) : (
                    <Button onClick={() => setCurrent((c) => c + 1)}>
                        Next <ChevronRight className="h-4 w-4" aria-hidden />
                    </Button>
                )}
            </div>
        </div>
    );
}

function StateCard({ icon, title, children }: { icon: ReactNode; title: string; children: ReactNode }) {
    return (
        <div className="mx-auto max-w-md px-4 py-16 text-center">
            <Card className="flex flex-col items-center gap-2 py-10">
                <div className="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-quiz-accent text-quiz-primary">
                    {icon}
                </div>
                <h2 className="text-xl font-semibold text-gray-900">{title}</h2>
                {children}
            </Card>
        </div>
    );
}
