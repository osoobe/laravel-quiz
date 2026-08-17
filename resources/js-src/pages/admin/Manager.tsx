import { useEffect, useRef, useState } from 'react';
import { useBlocker, useSearchParams } from 'react-router-dom';
import { Download, FileQuestion, Folder, ListChecks, Loader2, Tag, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { Tabs, TabPanel } from '../../components/ui/Tabs';
import { Button } from '../../components/ui/Button';
import { AdminCategoryApi, AdminDataApi, AdminQuestionApi, AdminQuizApi, AdminTopicApi } from '../../api/quiz';
import { apiErrorMessage } from '../../lib/apiErrorMessage';
import { ApiError } from '../../api/client';
import { assertBundleShape, runMultiPhaseImport } from '../../lib/importRunner';
import { ImportAllSummaryDialog, initialImportAllRunState } from '../../components/ImportAllSummaryDialog';
import { QuestionsTab } from './QuestionsTab';
import { QuizzesTab } from './QuizzesTab';
import { TopicsTab } from './TopicsTab';
import { CategoriesTab } from './CategoriesTab';

const TABS = [
    { value: 'questions', label: 'Questions', icon: <FileQuestion className="h-4 w-4" aria-hidden /> },
    { value: 'quizzes', label: 'Quizzes', icon: <ListChecks className="h-4 w-4" aria-hidden /> },
    { value: 'topics', label: 'Topics', icon: <Folder className="h-4 w-4" aria-hidden /> },
    { value: 'categories', label: 'Categories', icon: <Tag className="h-4 w-4" aria-hidden /> },
];
const TAB_VALUES = TABS.map((t) => t.value);
const DEFAULT_TAB = TAB_VALUES[0];

export function Manager() {
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab');
    const tab = requestedTab && TAB_VALUES.includes(requestedTab) ? requestedTab : DEFAULT_TAB;

    function setTab(next: string) {
        setSearchParams((prev) => {
            const params = new URLSearchParams(prev);
            params.set('tab', next);
            return params;
        });
    }

    const [exportingAll, setExportingAll] = useState(false);
    const [importAllState, setImportAllState] = useState(initialImportAllRunState);
    const importAllInput = useRef<HTMLInputElement>(null);

    // Any tab's open Add/Edit dialog reports its unsaved-edit status here (only one tab
    // is ever mounted at a time, so at most one can be dirty) — used to confirm before
    // an in-app navigation or a real page refresh/close would silently discard it.
    const [formDirty, setFormDirty] = useState(false);
    const blocker = useBlocker(formDirty);

    useEffect(() => {
        if (blocker.state !== 'blocked') return;

        if (window.confirm('You have unsaved changes. Leaving now will discard them. Continue?')) {
            blocker.proceed();
        } else {
            blocker.reset();
        }
    }, [blocker]);

    useEffect(() => {
        if (!formDirty) return;

        function handleBeforeUnload(event: BeforeUnloadEvent) {
            event.preventDefault();
            event.returnValue = '';
        }

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [formDirty]);

    async function importAll(file: File) {
        let data: unknown;

        try {
            data = JSON.parse(await file.text());
        } catch {
            toast.error('That file is not valid JSON');

            return;
        }

        try {
            assertBundleShape(data);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'That file is not an "Export All Data" bundle.');

            return;
        }

        setImportAllState({ ...initialImportAllRunState, open: true, running: true });

        try {
            const summary = await runMultiPhaseImport(
                [
                    { key: 'topics', label: 'topics', rows: data.topics ?? [], importChunk: (c) => AdminTopicApi.import(c) },
                    {
                        key: 'categories',
                        label: 'categories',
                        rows: data.categories ?? [],
                        importChunk: (c) => AdminCategoryApi.import(c),
                    },
                    {
                        key: 'questions',
                        label: 'questions',
                        rows: data.questions ?? [],
                        importChunk: (c) => AdminQuestionApi.import(c),
                    },
                    { key: 'quizzes', label: 'quizzes', rows: data.quizzes ?? [], importChunk: (c) => AdminQuizApi.import(c) },
                ],
                (progress) =>
                    setImportAllState((s) => ({
                        ...s,
                        phaseLabel: progress.phaseLabel,
                        phaseIndex: progress.phaseIndex,
                        totalPhases: progress.totalPhases,
                        progress: { done: progress.done, total: progress.total },
                    })),
            );

            setImportAllState((s) => ({ ...s, running: false, summary }));

            const totalImported = Object.values(summary).reduce((sum, r) => sum + r.imported, 0);
            const totalFailed = Object.values(summary).reduce((sum, r) => sum + r.failed, 0);
            toast.success(`Imported ${totalImported} records, ${totalFailed} failed`);
        } catch (error) {
            const message = error instanceof ApiError ? error.message : 'Import failed — check the file and try again.';
            setImportAllState((s) => ({ ...s, running: false, error: message }));
            toast.error(message);
        }
    }

    async function exportAll() {
        setExportingAll(true);

        try {
            const bundle = await AdminDataApi.exportAll();
            const blob = new Blob([JSON.stringify(bundle, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `quiz-manager-export-${new Date().toISOString().slice(0, 10)}.json`;
            link.click();
            URL.revokeObjectURL(url);

            const total =
                bundle.topics.length + bundle.categories.length + bundle.questions.length + bundle.quizzes.length;
            toast.success(
                `Exported ${total} records (${bundle.topics.length} topics, ${bundle.categories.length} categories, ` +
                    `${bundle.questions.length} questions, ${bundle.quizzes.length} quizzes)`,
            );
        } catch (error) {
            toast.error(apiErrorMessage(error, 'Failed to export data'));
        } finally {
            setExportingAll(false);
        }
    }

    return (
        <div className="mx-auto max-w-5xl px-4 py-10">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">Quiz Manager</h1>
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={() => importAllInput.current?.click()} disabled={importAllState.running}>
                        <Upload className="h-4 w-4" aria-hidden />
                        Import All Data
                    </Button>
                    <input
                        ref={importAllInput}
                        type="file"
                        accept=".json,application/json"
                        className="hidden"
                        onChange={(e) => {
                            if (e.target.files?.[0]) importAll(e.target.files[0]);
                            e.target.value = '';
                        }}
                    />
                    <Button variant="outline" onClick={exportAll} disabled={exportingAll}>
                        {exportingAll ? (
                            <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                        ) : (
                            <Download className="h-4 w-4" aria-hidden />
                        )}
                        Export All Data
                    </Button>
                </div>
            </div>

            <div className="mt-6">
                <Tabs value={tab} onValueChange={setTab} tabs={TABS}>
                    <TabPanel value="questions">
                        <QuestionsTab onDirtyChange={setFormDirty} />
                    </TabPanel>
                    <TabPanel value="quizzes">
                        <QuizzesTab onDirtyChange={setFormDirty} />
                    </TabPanel>
                    <TabPanel value="topics">
                        <TopicsTab onDirtyChange={setFormDirty} />
                    </TabPanel>
                    <TabPanel value="categories">
                        <CategoriesTab onDirtyChange={setFormDirty} />
                    </TabPanel>
                </Tabs>
            </div>

            <ImportAllSummaryDialog state={importAllState} onClose={() => setImportAllState(initialImportAllRunState)} />
        </div>
    );
}
