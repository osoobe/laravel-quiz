import { type HTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type Variant = 'solid' | 'secondary' | 'outline' | 'destructive';

const variants: Record<Variant, string> = {
    solid: 'bg-quiz-primary text-quiz-primary-foreground',
    secondary: 'bg-gray-100 text-gray-700',
    outline: 'border border-quiz-border text-gray-700',
    destructive: 'bg-red-50 text-red-600',
};

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
    variant?: Variant;
}

export function Badge({ className, variant = 'secondary', ...props }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
