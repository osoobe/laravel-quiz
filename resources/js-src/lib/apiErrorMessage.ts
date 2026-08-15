import { ApiError } from '../api/client';

/**
 * Prefers the first field-level validation message (e.g. "itemcode has already been
 * taken") over the generic top-level error message, which is much more useful for
 * pinpointing exactly what was wrong with a save.
 */
export function apiErrorMessage(error: unknown, fallback: string): string {
    if (error instanceof ApiError) {
        const firstFieldError = error.errors ? Object.values(error.errors)[0]?.[0] : undefined;

        return firstFieldError ?? error.message ?? fallback;
    }

    return fallback;
}
