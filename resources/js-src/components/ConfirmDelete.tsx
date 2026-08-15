import { useState } from 'react';
import { Trash2 } from 'lucide-react';
import { Dialog } from './ui/Dialog';
import { Button } from './ui/Button';

export function ConfirmDelete({
    label = 'Delete',
    description,
    onConfirm,
}: {
    label?: string;
    description: string;
    onConfirm: () => Promise<void> | void;
}) {
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="rounded p-1.5 text-red-500 hover:bg-red-50"
                aria-label={label}
            >
                <Trash2 className="h-4 w-4" aria-hidden />
            </button>

            <Dialog
                open={open}
                onOpenChange={setOpen}
                title={label}
                description={description}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={busy}
                            onClick={async () => {
                                setBusy(true);
                                try {
                                    await onConfirm();
                                    setOpen(false);
                                } finally {
                                    setBusy(false);
                                }
                            }}
                        >
                            {busy ? 'Deleting…' : 'Delete'}
                        </Button>
                    </>
                }
            >
                <p className="text-sm text-quiz-muted">This action cannot be undone.</p>
            </Dialog>
        </>
    );
}
