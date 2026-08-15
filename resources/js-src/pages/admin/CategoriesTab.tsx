import { useEffect, useRef, useState } from 'react';
import { Download, Loader2, Plus, Tag, Pencil, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { AdminCategoryApi } from '../../api/quiz';
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
import type { Category } from '../../api/types';

export function CategoriesTab() {
    const [categories, setCategories] = useState<Category[] | null>(null);
    const [editing, setEditing] = useState<Category | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [form, setForm] = useState({ name: '', description: '', is_active: true, itemcode: '' });
    const [exporting, setExporting] = useState(false);
    const [importState, setImportState] = useState(() => initialImportRunState('categories'));
    const importInput = useRef<HTMLInputElement>(null);

    function load() {
        AdminCategoryApi.index()
            .then((res) => setCategories(res.data))
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load categories');
                setCategories([]);
            });
    }

    useEffect(load, []);

    function openCreate() {
        setEditing(null);
        setForm({ name: '', description: '', is_active: true, itemcode: '' });
        setDialogOpen(true);
    }

    function openEdit(category: Category) {
        setEditing(category);
        setForm({
            name: category.name,
            description: category.description ?? '',
            is_active: category.is_active,
            itemcode: category.itemcode ?? '',
        });
        setDialogOpen(true);
    }

    async function save() {
        const payload = { ...form, itemcode: form.itemcode || null };

        try {
            if (editing) {
                await AdminCategoryApi.update(editing.id, payload);
                toast.success('Category updated');
            } else {
                await AdminCategoryApi.create(payload);
                toast.success('Category created');
            }
            setDialogOpen(false);
            load();
        } catch (error) {
            toast.error(apiErrorMessage(error, 'Failed to save category'));
        }
    }

    async function exportCategories() {
        setExporting(true);
        try {
            const data = await AdminCategoryApi.export();
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'quiz-categories.json';
            link.click();
            URL.revokeObjectURL(url);
            toast.success(`Exported ${data.length} categories`);
        } catch {
            toast.error('Failed to export categories');
        } finally {
            setExporting(false);
        }
    }

    async function importCategories(file: File) {
        let rows: unknown;

        try {
            rows = JSON.parse(await file.text());
        } catch {
            toast.error('That file is not valid JSON');

            return;
        }

        try {
            assertRowsLookLike(rows, 'name', 'categories');
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'That file is not a categories import.');

            return;
        }

        setImportState({
            ...initialImportRunState('categories'),
            open: true,
            running: true,
            progress: { done: 0, total: rows.length },
        });

        try {
            const summary = await runChunkedImport(rows, (chunk) => AdminCategoryApi.import(chunk), (done, total) =>
                setImportState((s) => ({ ...s, progress: { done, total } })),
            );
            setImportState((s) => ({ ...s, running: false, summary }));
            toast.success(`Imported ${summary.imported} categories, ${summary.failed} failed`);
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
                <p className="text-sm text-quiz-muted">{categories?.length ?? 0} categories</p>
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={exportCategories} disabled={exporting} aria-label="Export categories">
                        {exporting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : <Download className="h-4 w-4" aria-hidden />}
                    </Button>
                    <Button variant="outline" onClick={() => importInput.current?.click()} aria-label="Import categories">
                        <Upload className="h-4 w-4" aria-hidden />
                    </Button>
                    <input
                        ref={importInput}
                        type="file"
                        accept=".json,application/json"
                        className="hidden"
                        onChange={(e) => {
                            if (e.target.files?.[0]) importCategories(e.target.files[0]);
                            e.target.value = '';
                        }}
                    />
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" aria-hidden /> Add Category
                    </Button>
                </div>
            </div>

            {categories === null ? null : categories.length === 0 ? (
                <EmptyState icon={<Tag className="h-10 w-10" />} title="No categories yet" />
            ) : (
                <div className="space-y-2">
                    {categories.map((category) => (
                        <Card key={category.id} className="flex items-center justify-between gap-3">
                            <div>
                                <div className="flex items-center gap-2">
                                    <p className="font-medium text-gray-900">{category.name}</p>
                                    <ItemCodeBadge code={category.itemcode} />
                                </div>
                                {category.description && <p className="text-sm text-quiz-muted">{category.description}</p>}
                            </div>
                            <div className="flex items-center gap-2">
                                <Badge variant={category.is_active ? 'solid' : 'outline'}>
                                    {category.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                                <button onClick={() => openEdit(category)} aria-label="Edit category" className="rounded p-1.5 text-gray-500 hover:bg-gray-100">
                                    <Pencil className="h-4 w-4" aria-hidden />
                                </button>
                                <ConfirmDelete
                                    label="Delete category"
                                    description={`Delete "${category.name}"? Questions/quizzes referencing it will be unlinked.`}
                                    onConfirm={async () => {
                                        await AdminCategoryApi.destroy(category.id);
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
                title={editing ? 'Edit Category' : 'Add Category'}
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

            <ImportSummaryDialog state={importState} onClose={() => setImportState(initialImportRunState('categories'))} />
        </div>
    );
}
