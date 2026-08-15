import { Dialog } from './ui/Dialog';
import { Progress } from './ui/Progress';
import { Button } from './ui/Button';
import type { ImportSummary } from '../api/types';

export interface ImportAllRunState {
    open: boolean;
    running: boolean;
    phaseLabel: string;
    phaseIndex: number;
    totalPhases: number;
    progress: { done: number; total: number };
    summary: Record<string, ImportSummary> | null;
    error: string | null;
}

export const initialImportAllRunState: ImportAllRunState = {
    open: false,
    running: false,
    phaseLabel: '',
    phaseIndex: 0,
    totalPhases: 0,
    progress: { done: 0, total: 0 },
    summary: null,
    error: null,
};

/**
 * The multi-phase counterpart to ImportSummaryDialog — used only by "Import All
 * Data", which runs topics, categories, questions, and quizzes as separate phases
 * (in that dependency order) against the same file.
 */
export function ImportAllSummaryDialog({ state, onClose }: { state: ImportAllRunState; onClose: () => void }) {
    const title = state.running
        ? `Importing ${state.phaseLabel} (${state.phaseIndex + 1} of ${state.totalPhases})…`
        : state.error
          ? 'Import Failed'
          : 'Import Complete';

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
                        {state.progress.done} / {state.progress.total} {state.phaseLabel} processed…
                    </p>
                </div>
            ) : state.error ? (
                <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{state.error}</p>
            ) : (
                state.summary && (
                    <div className="space-y-2">
                        {Object.entries(state.summary).map(([key, result]) => (
                            <div key={key} className="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <span className="capitalize text-gray-900">{key}</span>
                                <span>
                                    <span className="font-semibold text-quiz-primary">{result.imported} imported</span>
                                    {result.failed > 0 && <span className="text-red-600"> · {result.failed} failed</span>}
                                </span>
                            </div>
                        ))}
                    </div>
                )
            )}
        </Dialog>
    );
}
