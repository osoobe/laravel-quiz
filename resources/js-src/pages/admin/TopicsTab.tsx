import { useEffect, useRef, useState } from 'react';
import { Download, Folder, Loader2, Pencil, Plus, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { AdminTopicApi } from '../../api/quiz';
import { ApiError } from '../../api/client';
import { assertRowsLookLike, runChunkedImport } from '../../lib/importRunner';
import { apiErrorMessage } from '../../lib/apiErrorMessage';
import { Card } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Dialog } from '../../components/ui/Dialog';
import { ConfirmDelete } from '../../components/ConfirmDelete';
import { EmptyState } from '../../components/EmptyState';
import { ImportSummaryDialog, initialImportRunState } from '../../components/ImportSummaryDialog';
import { ItemCodeField } from '../../components/ItemCodeField';
import { ItemCodeBadge } from '../../components/ItemCodeBadge';
import type { Topic } from '../../api/types';

export function TopicsTab() {
    const [topics, setTopics] = useState<Topic[] | null>(null);
    const [editing, setEditing] = useState<Topic | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [form, setForm] = useState({ name: '', description: '', is_active: true, itemcode: '' });
    const [exporting, setExporting] = useState(false);
    const [importState, setImportState] = useState(() => initialImportRunState('topics'));
    const importInput = useRef<HTMLInputElement>(null);

    function load() {
        AdminTopicApi.index()
            .then((res) => setTopics(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load topics');
                setTopics([]);
            });
    }

    useEffect(load, []);

    function openCreate() {
        setEditing(null);
        setForm({ name: '', description: '', is_active: true, itemcode: '' });
        setDialogOpen(true);
    }

    function openEdit(topic: Topic) {
        setEditing(topic);
        setForm({
            name: topic.name,
            description: topic.description ?? '',
            is_active: topic.is_active,
            itemcode: topic.itemcode ?? '',
        });
        setDialogOpen(true);
    }

    async function save() {
        const payload = { ...form, itemcode: form.itemcode || null };

        try {
            if (editing) {
                await AdminTopicApi.update(editing.id, payload);
                toast.success('Topic updated');
            } else {
                await AdminTopicApi.create(payload);
                toast.success('Topic created');
            }
            setDialogOpen(false);
            load();
        } catch (error) {
            toast.error(apiErrorMessage(error, 'Failed to save topic'));
        }
    }

    async function exportTopics() {
        setExporting(true);
        try {
            const data = await AdminTopicApi.export();
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'quiz-topics.json';
            link.click();
            URL.revokeObjectURL(url);
            toast.success(`Exported ${data.length} topics`);
        } catch {
            toast.error('Failed to export topics');
        } finally {
            setExporting(false);
        }
    }

    async function importTopics(file: File) {
        let rows: unknown;

        try {
            rows = JSON.parse(await file.text());
        } catch {
            toast.error('That file is not valid JSON');

            return;
        }

        try {
            assertRowsLookLike(rows, 'name', 'topics');
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'That file is not a topics import.');

            return;
        }

        setImportState({ ...initialImportRunState('topics'), open: true, running: true, progress: { done: 0, total: rows.length } });

        try {
            const summary = await runChunkedImport(rows, (chunk) => AdminTopicApi.import(chunk), (done, total) =>
                setImportState((s) => ({ ...s, progress: { done, total } })),
            );
            setImportState((s) => ({ ...s, running: false, summary }));
            toast.success(`Imported ${summary.imported} topics, ${summary.failed} failed`);
            load();
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'Import failed — check the file and try again.';
            setImportState((s) => ({ ...s, running: false, error: message }));
            toast.error(message);
        }
    }

    return (
        <div className="mt-4">
            <div className="mb-4 flex items-center justify-between">
                <p className="text-sm text-quiz-muted">{topics?.length ?? 0} topics</p>
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={exportTopics} disabled={exporting} aria-label="Export topics">
                        {exporting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : <Download className="h-4 w-4" aria-hidden />}
                    </Button>
                    <Button variant="outline" onClick={() => importInput.current?.click()} aria-label="Import topics">
                        <Upload className="h-4 w-4" aria-hidden />
                    </Button>
                    <input
                        ref={importInput}
                        type="file"
                        accept=".json,application/json"
                        className="hidden"
                        onChange={(e) => {
                            if (e.target.files?.[0]) importTopics(e.target.files[0]);
                            e.target.value = '';
                        }}
                    />
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" aria-hidden /> Add Topic
                    </Button>
                </div>
            </div>

            {topics === null ? null : topics.length === 0 ? (
                <EmptyState icon={<Folder className="h-10 w-10" />} title="No topics yet" />
            ) : (
                <div className="space-y-2">
                    {topics.map((topic) => (
                        <Card key={topic.id} className="flex items-center justify-between gap-3">
                            <div>
                                <div className="flex items-center gap-2">
                                    <p className="font-medium text-gray-900">{topic.name}</p>
                                    <ItemCodeBadge code={topic.itemcode} />
                                </div>
                                {topic.description && <p className="text-sm text-quiz-muted">{topic.description}</p>}
                            </div>
                            <div className="flex items-center gap-2">
                                <Badge variant={topic.is_active ? 'solid' : 'outline'}>
                                    {topic.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                                <button onClick={() => openEdit(topic)} aria-label="Edit topic" className="rounded p-1.5 text-gray-500 hover:bg-gray-100">
                                    <Pencil className="h-4 w-4" aria-hidden />
                                </button>
                                <ConfirmDelete
                                    label="Delete topic"
                                    description={`Delete "${topic.name}"? Questions/quizzes referencing it will be unlinked.`}
                                    onConfirm={async () => {
                                        await AdminTopicApi.destroy(topic.id);
                                        load();
                                    }}
                                />
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            <Dialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                title={editing ? 'Edit Topic' : 'Add Topic'}
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
                <div className="space-y-3">
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
                    <ItemCodeField value={form.itemcode} onChange={(itemcode) => setForm({ ...form, itemcode })} />
                    <label className="flex items-center gap-2 text-sm text-gray-900">
                        <input
                            type="checkbox"
                            checked={form.is_active}
                            onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                        />
                        Active
                    </label>
                </div>
            </Dialog>

            <ImportSummaryDialog state={importState} onClose={() => setImportState(initialImportRunState('topics'))} />
        </div>
    );
}
