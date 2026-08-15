import type { ImportSummary } from '../api/types';
import type { ExportAllBundle } from '../api/quiz';

/**
 * Cheap client-side sanity check before sending anything over the network — catches
 * the common mistake of uploading the wrong sample file to a tab (e.g. quiz-questions.json
 * on the Topics tab) with an immediate, specific message instead of a round-trip 422.
 */
export function assertRowsLookLike(
    rows: unknown,
    requiredKey: string,
    label: string,
): asserts rows is Record<string, unknown>[] {
    if (!Array.isArray(rows)) {
        throw new Error(`This ${label} file must contain a JSON array, not an object.`);
    }

    if (rows.length === 0) {
        throw new Error(`This ${label} file is empty.`);
    }

    const firstInvalid = rows.findIndex((row) => typeof row !== 'object' || row === null || !(requiredKey in row));

    if (firstInvalid !== -1) {
        throw new Error(
            `This doesn't look like a ${label} file — row ${firstInvalid + 1} is missing a "${requiredKey}" field.`,
        );
    }
}

/**
 * Splits a large import into sequential chunked requests so the UI can show real,
 * granular progress instead of one opaque "importing…" spinner for the whole batch.
 */
export async function runChunkedImport<T>(
    rows: T[],
    importChunk: (chunk: T[]) => Promise<ImportSummary>,
    onProgress: (done: number, total: number) => void,
    chunkSize = 25,
): Promise<ImportSummary> {
    const total = rows.length;
    let imported = 0;
    let failed = 0;
    const errors: ImportSummary['errors'] = [];
    let done = 0;

    for (let start = 0; start < rows.length; start += chunkSize) {
        const chunk = rows.slice(start, start + chunkSize);
        const result = await importChunk(chunk);

        imported += result.imported;
        failed += result.failed;
        // Row indexes returned by the backend are relative to the chunk — offset them
        // back to the row's position in the full file for a meaningful error message.
        errors.push(...result.errors.map((error) => ({ ...error, row: error.row + start })));

        done += chunk.length;
        onProgress(done, total);
    }

    return { imported, failed, errors };
}

/**
 * Validates an uploaded file matches the shape produced by "Export All Data"
 * ({ topics, categories, questions, quizzes }, each an array) before running
 * anything — sections may be omitted, but present ones must be arrays.
 */
export function assertBundleShape(data: unknown): asserts data is Partial<ExportAllBundle> {
    if (typeof data !== 'object' || data === null || Array.isArray(data)) {
        throw new Error(
            'This file must be a JSON object with topics/categories/questions/quizzes arrays, matching the "Export All Data" format.',
        );
    }

    const bundle = data as Record<string, unknown>;
    const knownKeys = ['topics', 'categories', 'questions', 'quizzes'] as const;

    for (const key of knownKeys) {
        if (key in bundle && !Array.isArray(bundle[key])) {
            throw new Error(`"${key}" must be an array in this file.`);
        }
    }

    const hasAnyRows = knownKeys.some((key) => Array.isArray(bundle[key]) && (bundle[key] as unknown[]).length > 0);

    if (!hasAnyRows) {
        throw new Error('This file does not contain any topics, categories, questions, or quizzes to import.');
    }
}

export interface ImportPhase {
    key: string;
    label: string;
    rows: Record<string, unknown>[];
    importChunk: (chunk: Record<string, unknown>[]) => Promise<ImportSummary>;
}

export interface MultiPhaseProgress {
    phaseLabel: string;
    phaseIndex: number;
    totalPhases: number;
    done: number;
    total: number;
}

/**
 * Runs each phase's rows through runChunkedImport in order — critical here, since
 * questions and quizzes reference topics/categories by name and must be imported
 * after them. Phases with no rows are skipped entirely rather than shown as an
 * empty 0/0 step.
 */
export async function runMultiPhaseImport(
    phases: ImportPhase[],
    onProgress: (progress: MultiPhaseProgress) => void,
): Promise<Record<string, ImportSummary>> {
    const activePhases = phases.filter((phase) => phase.rows.length > 0);
    const results: Record<string, ImportSummary> = {};

    for (let index = 0; index < activePhases.length; index++) {
        const phase = activePhases[index];

        results[phase.key] = await runChunkedImport(phase.rows, phase.importChunk, (done, total) =>
            onProgress({ phaseLabel: phase.label, phaseIndex: index, totalPhases: activePhases.length, done, total }),
        );
    }

    return results;
}
