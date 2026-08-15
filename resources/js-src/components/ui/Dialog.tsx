import * as RadixDialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { type ReactNode } from 'react';
import { cn } from '../../lib/cn';

export function Dialog({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
    className,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
    className?: string;
}) {
    return (
        <RadixDialog.Root open={open} onOpenChange={onOpenChange}>
            <RadixDialog.Portal>
                <RadixDialog.Overlay className="fixed inset-0 z-40 bg-black/40" />
                <RadixDialog.Content
                    className={cn(
                        'fixed left-1/2 top-1/2 z-50 max-h-[90vh] w-[92vw] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl bg-white p-6 shadow-lg focus:outline-none',
                        className,
                    )}
                >
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <RadixDialog.Title className="text-lg font-semibold text-gray-900">{title}</RadixDialog.Title>
                            {description && (
                                <RadixDialog.Description className="mt-1 text-sm text-quiz-muted">
                                    {description}
                                </RadixDialog.Description>
                            )}
                        </div>
                        <RadixDialog.Close className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                            <X className="h-4 w-4" aria-hidden />
                            <span className="sr-only">Close</span>
                        </RadixDialog.Close>
                    </div>

                    <div>{children}</div>

                    {footer && <div className="mt-6 flex justify-end gap-2">{footer}</div>}
                </RadixDialog.Content>
            </RadixDialog.Portal>
        </RadixDialog.Root>
    );
}
