import { type ReactNode } from 'react';

export function EmptyState({
    icon,
    title,
    action,
}: {
    icon: ReactNode;
    title: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-quiz-border py-16 text-center">
            <div className="text-gray-300">{icon}</div>
            <p className="text-sm text-quiz-muted">{title}</p>
            {action}
        </div>
    );
}
