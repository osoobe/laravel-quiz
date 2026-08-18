import { useEffect, useRef, useState } from 'react';
import { Download, FileQuestion, Loader2, Plus, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { AdminCategoryApi, AdminQuestionApi, AdminTopicApi } from '../../api/quiz';
import { ApiError } from '../../api/client';
import { assertRowsLookLike, runChunkedImport } from '../../lib/importRunner';
import { apiErrorMessage } from '../../lib/apiErrorMessage';
import { Button } from '../../components/ui/Button';
import { Dialog } from '../../components/ui/Dialog';
import { QuestionCard } from '../../components/QuestionCard';
import { AnswerRepeater, type AnswerDraft } from '../../components/AnswerRepeater';
import { EmptyState } from '../../components/EmptyState';
import { ImportSummaryDialog, initialImportRunState } from '../../components/ImportSummaryDialog';
import { ItemCodeField } from '../../components/ItemCodeField';
import { useDirtyFormReporter } from '../../lib/useDirtyFormReporter';
import type { Category, Difficulty, Question, QuestionType, Topic } from '../../api/types';

const emptyAnswers: AnswerDraft[] = [
    { text: '', is_correct: false },
    { text: '', is_correct: false },
];

const emptyForm = {
    question: '',
    description: '',
    topic_id: '',
    category_id: '',
    difficulty: 'medium' as Difficulty,
    question_type: 'radio' as QuestionType,
    answers: emptyAnswers,
    itemcode: '',
};

export function QuestionsTab({ onDirtyChange }: { onDirtyChange: (dirty: boolean) => void }) {
    const [questions, setQuestions] = useState<Question[] | null>(null);
    const [topics, setTopics] = useState<Topic[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [search, setSearch] = useState('');
    const [difficulty, setDifficulty] = useState('');
    const [editing, setEditing] = useState<Question | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [exporting, setExporting] = useState(false);
    const [importState, setImportState] = useState(() => initialImportRunState('questions'));
    const importInput = useRef<HTMLInputElement>(null);

    const [form, setForm] = useState(emptyForm);
    const [formBaseline, setFormBaseline] = useState(emptyForm);

    useDirtyFormReporter(dialogOpen && JSON.stringify(form) !== JSON.stringify(formBaseline), onDirtyChange);

    function load() {
        AdminQuestionApi.index({ search: search || undefined, difficulty: difficulty || undefined })
            .then((res) => setQuestions(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load questions');
                setQuestions([]);
            });
    }

    useEffect(load, [search, difficulty]);
    useEffect(() => {
        AdminTopicApi.index()
            .then((res) => setTopics(res.data))
            .catch(() => toast.error('Failed to load topics for the question form'));
        AdminCategoryApi.index()
            .then((res) => setCategories(res.data))
            .catch(() => toast.error('Failed to load categories for the question form'));
    }, []);

    function openCreate() {
        setEditing(null);
        setForm(emptyForm);
        setFormBaseline(emptyForm);
        setDialogOpen(true);
    }

    function openEdit(question: Question) {
        const next = {
            question: question.question,
            description: question.description ?? '',
            topic_id: question.topic?.id ?? '',
            category_id: question.category?.id ?? '',
            difficulty: question.difficulty,
            question_type: question.question_type,
            answers: question.answers.map((a) => ({ id: a.id, text: a.text, is_correct: !!a.is_correct })),
            itemcode: question.itemcode ?? '',
        };
        setEditing(question);
        setForm(next);
        setFormBaseline(next);
        setDialogOpen(true);
    }

    const hasCorrectAnswer = form.answers.some((a) => a.is_correct);
    const canSave = form.question.trim().length > 0 && hasCorrectAnswer;

    async function save() {
        const payload = {
            question: form.question,
            description: form.description || null,
            topic_id: form.topic_id || null,
            category_id: form.category_id || null,
            difficulty: form.difficulty,
            question_type: form.question_type,
            answers: form.answers,
            itemcode: form.itemcode || null,
        };

        try {
            if (editing) {
                await AdminQuestionApi.update(editing.id, payload);
                toast.success('Question updated');
            } else {
                await AdminQuestionApi.create(payload);
                toast.success('Question created');
            }
            setDialogOpen(false);
            load();
        } catch (error) {
            toast.error(apiErrorMessage(error, 'Failed to save question'));
        }
    }

    async function exportQuestions() {
        setExporting(true);
        try {
            const data = await AdminQuestionApi.export();
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'quiz-questions.json';
            link.click();
            URL.revokeObjectURL(url);
            toast.success(`Exported ${data.length} questions`);
        } catch {
            toast.error('Failed to export questions');
        } finally {
            setExporting(false);
        }
    }

    async function importQuestions(file: File) {
        let rows: unknown;

        try {
            rows = JSON.parse(await file.text());
        } catch {
            toast.error('That file is not valid JSON');

            return;
        }

        try {
            assertRowsLookLike(rows, 'question', 'questions');
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'That file is not a questions import.');

            return;
        }

        setImportState({
            ...initialImportRunState('questions'),
            open: true,
            running: true,
            progress: { done: 0, total: rows.length },
        });

        try {
            const summary = await runChunkedImport(rows, (chunk) => AdminQuestionApi.import(chunk), (done, total) =>
                setImportState((s) => ({ ...s, progress: { done, total } })),
            );
            setImportState((s) => ({ ...s, running: false, summary }));
            toast.success(`Imported ${summary.imported} questions, ${summary.failed} failed`);
            load();
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'Import failed — check the file and try again.';
            setImportState((s) => ({ ...s, running: false, error: message }));
            toast.error(message);
        }
    }

    return (
        <div className="mt-4">
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search questions..."
                    className="min-w-[220px] flex-1 rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                />
                <select
                    value={difficulty}
                    onChange={(e) => setDifficulty(e.target.value)}
                    className="rounded-lg border border-quiz-border px-3 py-2 text-sm"
                >
                    <option value="">All Levels</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                    <option value="expert">Expert</option>
                </select>
                <Button variant="outline" onClick={exportQuestions} disabled={exporting} aria-label="Export questions">
                    {exporting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : <Download className="h-4 w-4" aria-hidden />}
                </Button>
                <Button variant="outline" onClick={() => importInput.current?.click()} aria-label="Import questions">
                    <Upload className="h-4 w-4" aria-hidden />
                </Button>
                <input
                    ref={importInput}
                    type="file"
                    accept=".json,application/json"
                    className="hidden"
                    onChange={(e) => {
                        if (e.target.files?.[0]) importQuestions(e.target.files[0]);
                        e.target.value = '';
                    }}
                />
                <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" aria-hidden /> Add Question
                </Button>
            </div>

            <p className="mb-3 text-sm text-quiz-muted">{questions?.length ?? 0} questions</p>

            {questions === null ? null : questions.length === 0 ? (
                <EmptyState icon={<FileQuestion className="h-10 w-10" />} title="No questions found" />
            ) : (
                <div className="space-y-3">
                    {questions.map((question) => (
                        <QuestionCard
                            key={question.id}
                            question={question}
                            onEdit={() => openEdit(question)}
                            onDelete={async () => {
                                await AdminQuestionApi.destroy(question.id);
                                load();
                            }}
                        />
                    ))}
                </div>
            )}

            <Dialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                title={editing ? 'Edit Question' : 'Add Question'}
                description="Create a technical question with multiple choice answers."
                footer={
                    <>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={save} disabled={!canSave}>
                            {editing ? 'Save' : 'Create'}
                        </Button>
                    </>
                }
            >
                <div className="space-y-3">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-900">Question *</label>
                        <textarea
                            value={form.question}
                            onChange={(e) => setForm({ ...form, question: e.target.value })}
                            placeholder="Enter the question..."
                            className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-900">Description (optional)</label>
                        <textarea
                            value={form.description}
                            onChange={(e) => setForm({ ...form, description: e.target.value })}
                            placeholder="Additional context or explanation..."
                            className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Topic</label>
                            <select
                                value={form.topic_id}
                                onChange={(e) => setForm({ ...form, topic_id: e.target.value })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            >
                                <option value="">Select topic</option>
                                {topics.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Category</label>
                            <select
                                value={form.category_id}
                                onChange={(e) => setForm({ ...form, category_id: e.target.value })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            >
                                <option value="">Select category</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Difficulty *</label>
                            <select
                                value={form.difficulty}
                                onChange={(e) => setForm({ ...form, difficulty: e.target.value as Difficulty })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            >
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                                <option value="expert">Expert</option>
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">Answer Type *</label>
                            <select
                                value={form.question_type}
                                onChange={(e) => setForm({ ...form, question_type: e.target.value as QuestionType })}
                                className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm"
                            >
                                <option value="radio">Single Answer (Radio)</option>
                                <option value="checkbox">Multiple Answers (Checkbox)</option>
                            </select>
                        </div>
                    </div>

                    <AnswerRepeater
                        answers={form.answers}
                        questionType={form.question_type}
                        onChange={(answers) => setForm({ ...form, answers })}
                    />

                    <ItemCodeField value={form.itemcode} onChange={(itemcode) => setForm({ ...form, itemcode })} />
                </div>
            </Dialog>

            <ImportSummaryDialog state={importState} onClose={() => setImportState(initialImportRunState('questions'))} />
        </div>
    );
}
