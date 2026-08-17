import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Award, Eye, EyeOff, ListChecks, Pencil, Plus, Trophy, UserPlus } from 'lucide-react';
import { toast } from 'sonner';
import { AdminCategoryApi, AdminQuizApi, AdminTopicApi } from '../../api/quiz';
import { ApiError } from '../../api/client';
import { apiErrorMessage } from '../../lib/apiErrorMessage';
import { Card } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Dialog } from '../../components/ui/Dialog';
import { ConfirmDelete } from '../../components/ConfirmDelete';
import { EmptyState } from '../../components/EmptyState';
import { DifficultyBadge } from '../../components/DifficultyBadge';
import { AudienceBadge } from '../../components/AudienceBadge';
import { ItemCodeField } from '../../components/ItemCodeField';
import { ItemCodeBadge } from '../../components/ItemCodeBadge';
import { useDirtyFormReporter } from '../../lib/useDirtyFormReporter';
import type { Category, Difficulty, Quiz, Topic } from '../../api/types';

interface QuizForm {
    name: string;
    description: string;
    topic_ids: string[];
    category_ids: string[];
    difficulty: Difficulty | '';
    question_count: number;
    time_limit_minutes: string;
    max_attempts: number;
    audience: 'everyone' | 'logged_in' | 'private';
    randomize_questions: boolean;
    is_active: boolean;
    itemcode: string;
}

const emptyForm: QuizForm = {
    name: '',
    description: '',
    topic_ids: [],
    category_ids: [],
    difficulty: '',
    question_count: 10,
    time_limit_minutes: '',
    max_attempts: 1,
    audience: 'everyone',
    randomize_questions: true,
    is_active: true,
    itemcode: '',
};

export function QuizzesTab({ onDirtyChange }: { onDirtyChange: (dirty: boolean) => void }) {
    const [quizzes, setQuizzes] = useState<Quiz[] | null>(null);
    const [topics, setTopics] = useState<Topic[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [editing, setEditing] = useState<Quiz | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [form, setForm] = useState<QuizForm>(emptyForm);
    const [formBaseline, setFormBaseline] = useState<QuizForm>(emptyForm);

    useDirtyFormReporter(dialogOpen && JSON.stringify(form) !== JSON.stringify(formBaseline), onDirtyChange);

    function load() {
        AdminQuizApi.index()
            .then((res) => setQuizzes(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load quizzes');
                setQuizzes([]);
            });
    }

    useEffect(load, []);
    useEffect(() => {
        AdminTopicApi.index()
            .then((res) => setTopics(res.data))
            .catch(() => toast.error('Failed to load topics for the quiz form'));
        AdminCategoryApi.index()
            .then((res) => setCategories(res.data))
            .catch(() => toast.error('Failed to load categories for the quiz form'));
    }, []);

    function openCreate() {
        setEditing(null);
        setForm(emptyForm);
        setFormBaseline(emptyForm);
        setDialogOpen(true);
    }

    function openEdit(quiz: Quiz) {
        const next: QuizForm = {
            name: quiz.name,
            description: quiz.description ?? '',
            topic_ids: quiz.topic_ids,
            category_ids: quiz.category_ids,
            difficulty: quiz.difficulty ?? '',
            question_count: quiz.question_count,
            time_limit_minutes: quiz.time_limit_minutes ? String(quiz.time_limit_minutes) : '',
            max_attempts: quiz.max_attempts,
            audience: quiz.audience as QuizForm['audience'],
            randomize_questions: quiz.randomize_questions,
            is_active: quiz.is_active,
            itemcode: quiz.itemcode ?? '',
        };
        setEditing(quiz);
        setForm(next);
        setFormBaseline(next);
        setDialogOpen(true);
    }

    function toggle(list: string[], id: string): string[] {
        return list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
    }

    async function save() {
        const payload = {
            ...form,
            difficulty: form.difficulty || null,
            time_limit_minutes: form.time_limit_minutes ? Number(form.time_limit_minutes) : null,
            itemcode: form.itemcode || null,
        };

        try {
            if (editing) {
                await AdminQuizApi.update(editing.id, payload);
                toast.success('Quiz updated');
            } else {
                await AdminQuizApi.create(payload);
                toast.success('Quiz created');
            }
            setDialogOpen(false);
            load();
        } catch (error) {
            toast.error(apiErrorMessage(error, 'Failed to save quiz'));
        }
    }

    return (
        <div className="mt-4">
            <div className="mb-4 flex items-center justify-between">
                <p className="text-sm text-quiz-muted">{quizzes?.length ?? 0} quiz(zes)</p>
                <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" aria-hidden /> Create Quiz
                </Button>
            </div>

            {quizzes === null ? null : quizzes.length === 0 ? (
                <EmptyState icon={<ListChecks className="h-10 w-10" />} title="No quizzes yet" />
            ) : (
                <div className="space-y-3">
                    {quizzes.map((quiz) => (
                        <Card key={quiz.id}>
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="font-semibold text-gray-900">{quiz.name}</p>
                                        <ItemCodeBadge code={quiz.itemcode} />
                                    </div>
                                    {quiz.description && (
                                        <p className="mt-0.5 line-clamp-2 text-sm text-quiz-muted">{quiz.description}</p>
                                    )}
                                </div>
                                <div className="flex shrink-0 items-center gap-1">
                                    {quiz.is_active ? (
                                        <Eye className="h-4 w-4 text-quiz-primary" aria-label="Active" />
                                    ) : (
                                        <EyeOff className="h-4 w-4 text-gray-400" aria-label="Inactive" />
                                    )}
                                    <button onClick={() => openEdit(quiz)} aria-label="Edit quiz" className="rounded p-1.5 text-gray-500 hover:bg-gray-100">
                                        <Pencil className="h-4 w-4" aria-hidden />
                                    </button>
                                    <ConfirmDelete
                                        label="Delete quiz"
                                        description={`Delete "${quiz.name}"?`}
                                        onConfirm={async () => {
                                            await AdminQuizApi.destroy(quiz.id);
                                            load();
                                        }}
                                    />
                                </div>
                            </div>

                            <div className="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="outline">{quiz.question_count} questions</Badge>
                                {quiz.time_limit_minutes && <Badge variant="outline">{quiz.time_limit_minutes} min</Badge>}
                                <DifficultyBadge difficulty={quiz.difficulty} />
                                <AudienceBadge audience={quiz.audience} isScoped={quiz.is_scoped} />
                            </div>

                            <div className="mt-3 flex flex-wrap gap-2">
                                <Link
                                    to={`/${quiz.id}/results`}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-quiz-border px-3 py-1.5 text-sm font-medium hover:bg-gray-50"
                                >
                                    <Award className="h-4 w-4" aria-hidden /> Results
                                </Link>
                                <Link
                                    to={`/${quiz.id}/leaderboard`}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-quiz-border px-3 py-1.5 text-sm font-medium hover:bg-gray-50"
                                >
                                    <Trophy className="h-4 w-4" aria-hidden /> Leaderboard
                                </Link>
                                {quiz.audience === 'private' && (
                                    <Link
                                        to={`/admin/quizzes/${quiz.id}/invitations`}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-quiz-border px-3 py-1.5 text-sm font-medium hover:bg-gray-50"
                                    >
                                        <UserPlus className="h-4 w-4" aria-hidden /> Invitations
                                    </Link>
                                )}
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            <Dialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                title={editing ? 'Edit Quiz' : 'Create Quiz'}
                className="max-w-xl"
                footer={
                    <>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={save} disabled={!form.name}>
                            {editing ? 'Save' : 'Create'}
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-900">Name *</label>
                        <input
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-900">Description</label>
                        <textarea
                            value={form.description}
                            onChange={(e) => setForm({ ...form, description: e.target.value })}
                            className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <p className="mb-1 text-sm font-medium text-gray-900">Topics</p>
                            <div className="max-h-32 space-y-1 overflow-y-auto rounded-lg border border-quiz-border p-2">
                                {topics.map((topic) => (
                                    <label key={topic.id} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.topic_ids.includes(topic.id)}
                                            onChange={() => setForm({ ...form, topic_ids: toggle(form.topic_ids, topic.id) })}
                                        />
                                        {topic.name}
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div>
                            <p className="mb-1 text-sm font-medium text-gray-900">Categories</p>
                            <div className="max-h-32 space-y-1 overflow-y-auto rounded-lg border border-quiz-border p-2">
                                {categories.map((category) => (
                                    <label key={category.id} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.category_ids.includes(category.id)}
                                            onChange={() =>
                                                setForm({ ...form, category_ids: toggle(form.category_ids, category.id) })
                                            }
                                        />
                                        {category.name}
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Difficulty</label>
                            <select
                                value={form.difficulty}
                                onChange={(e) => setForm({ ...form, difficulty: e.target.value as Difficulty | '' })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            >
                                <option value="">Mixed</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                                <option value="expert">Expert</option>
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Number of questions</label>
                            <input
                                type="number"
                                min={1}
                                value={form.question_count}
                                onChange={(e) => setForm({ ...form, question_count: Number(e.target.value) })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Time limit (minutes)</label>
                            <input
                                type="number"
                                min={1}
                                placeholder="Untimed"
                                value={form.time_limit_minutes}
                                onChange={(e) => setForm({ ...form, time_limit_minutes: e.target.value })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Max attempts</label>
                            <input
                                type="number"
                                min={1}
                                value={form.max_attempts}
                                onChange={(e) => setForm({ ...form, max_attempts: Number(e.target.value) })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-900">Audience</label>
                        <select
                            value={form.audience}
                            disabled={editing?.is_scoped}
                            onChange={(e) => setForm({ ...form, audience: e.target.value as QuizForm['audience'] })}
                            className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm disabled:bg-gray-50"
                        >
                            <option value="everyone">Everyone</option>
                            <option value="logged_in">Logged-in users</option>
                            <option value="private">Private (invite only)</option>
                        </select>
                    </div>

                    {form.audience === 'private' && (
                        <p className="text-xs text-quiz-muted">
                            {editing
                                ? 'Manage invitations from the Invitations button on the quiz card.'
                                : 'Save the quiz first to manage invitations.'}
                        </p>
                    )}

                    <ItemCodeField value={form.itemcode} onChange={(itemcode) => setForm({ ...form, itemcode })} />

                    <div className="flex gap-6">
                        <label className="flex items-center gap-2 text-sm text-gray-900">
                            <input
                                type="checkbox"
                                checked={form.randomize_questions}
                                onChange={(e) => setForm({ ...form, randomize_questions: e.target.checked })}
                            />
                            Randomize questions
                        </label>
                        <label className="flex items-center gap-2 text-sm text-gray-900">
                            <input
                                type="checkbox"
                                checked={form.is_active}
                                onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                            />
                            Active
                        </label>
                    </div>
                </div>
            </Dialog>
        </div>
    );
}
