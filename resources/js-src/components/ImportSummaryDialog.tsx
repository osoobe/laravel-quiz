import { AlertTriangle } from 'lucide-react';
import { Dialog } from './ui/Dialog';
import { Progress } from './ui/Progress';
import { Button } from './ui/Button';
import type { ImportSummary } from '../api/types';

export interface ImportRunState {
    open: boolean;
    running: boolean;
    label: string;
    progress: { done: number; total: number };
    summary: ImportSummary | null;
    error: string | null;
}

export const initialImportRunState = (label: string): ImportRunState => ({
    open: false,
    running: false,
    label,
    progress: { done: 0, total: 0 },
    summary: null,
    error: null,
});

/**
 * Shared by the Questions/Categories/Topics import flows — shows a live progress bar
 * while runChunkedImport() works through the file, then either a final imported/failed
 * summary with a per-row error list, or a persistent error message if the run couldn't
 * complete at all (invalid file, network/auth failure). Closing is blocked while running.
 */
export function ImportSummaryDialog({ state, onClose }: { state: ImportRunState; onClose: () => void }) {
    const title = state.running ? `Importing ${state.label}…` : state.error ? 'Import Failed' : 'Import Complete';

    return (
        <Dialog
            open={state.open}
            onOpenChange={(open) => {
                if (!open && !state.running) onClose();
            }}
            title={title}
            footer={!state.running ? <Button onClick={onClose}>Done</Button> : undefined}
        >
            {state.running ? (
                <div className="space-y-2">
                    <Progress value={(state.progress.done / Math.max(1, state.progress.total)) * 100} />
                    <p className="text-sm text-quiz-muted">
                        {state.progress.done} / {state.progress.total} rows processed…
                    </p>
                </div>
            ) : state.error ? (
                <div className="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                    <AlertTriangle className="h-4 w-4 shrink-0 translate-y-0.5" aria-hidden />
                    <p>{state.error}</p>
                </div>
            ) : (
                state.summary && (
                    <div className="space-y-3">
                        <p className="text-sm text-gray-900">
                            <span className="font-semibold text-quiz-primary">{state.summary.imported} imported</span>
                            {state.summary.failed > 0 && (
                                <span className="text-red-600"> · {state.summary.failed} failed</span>
                            )}
                        </p>
                        {state.summary.errors.length > 0 && (
                            <div className="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-quiz-border p-2 text-xs text-red-600">
                                {state.summary.errors.map((error, index) => (
                                    <p key={index}>
                                        Row {error.row + 1}: {error.message}
                                    </p>
                                ))}
                            </div>
                        )}
                    </div>
                )
            )}
        </Dialog>
    );
}
