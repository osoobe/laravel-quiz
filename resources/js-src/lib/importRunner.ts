import type { ImportSummary } from '../api/types';

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
