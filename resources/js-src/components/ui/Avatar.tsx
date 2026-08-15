import { User as UserIcon } from 'lucide-react';
import { cn } from '../../lib/cn';

export function Avatar({ name, src, className }: { name: string; src?: string | null; className?: string }) {
    return (
        <div
            className={cn(
                'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-gray-500',
                className,
            )}
        >
            {src ? (
                <img src={src} alt={name} className="h-full w-full object-cover" />
            ) : (
                <UserIcon className="h-4.5 w-4.5" aria-hidden />
            )}
        </div>
    );
}
