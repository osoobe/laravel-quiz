import { useEffect } from 'react';

/**
 * Reports a tab's "does its open dialog have unsaved edits" status up to Manager,
 * which uses the aggregate to guard page navigation/refresh. Reports false on
 * unmount so switching away (once permitted) doesn't leave a stale dirty flag.
 */
export function useDirtyFormReporter(isDirty: boolean, onDirtyChange: (dirty: boolean) => void) {
    useEffect(() => {
        onDirtyChange(isDirty);
    }, [isDirty, onDirtyChange]);

    useEffect(() => () => onDirtyChange(false), [onDirtyChange]);
}
