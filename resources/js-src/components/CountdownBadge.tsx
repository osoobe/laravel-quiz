import { useEffect, useRef, useState } from 'react';
import { Clock } from 'lucide-react';
import { Badge } from './ui/Badge';

export function CountdownBadge({ expiresAt, onExpire }: { expiresAt: string; onExpire: () => void }) {
    const [remaining, setRemaining] = useState(() => secondsUntil(expiresAt));
    const fired = useRef(false);

    useEffect(() => {
        const interval = setInterval(() => {
            const next = secondsUntil(expiresAt);
            setRemaining(next);

            if (next <= 0 && !fired.current) {
                fired.current = true;
                onExpire();
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [expiresAt, onExpire]);

    const minutes = Math.floor(Math.max(0, remaining) / 60);
    const seconds = Math.max(0, remaining) % 60;

    return (
        <Badge variant={remaining < 60 ? 'destructive' : 'secondary'} aria-live="polite">
            <Clock className="h-3 w-3" aria-hidden />
            {minutes}:{String(seconds).padStart(2, '0')}
        </Badge>
    );
}

function secondsUntil(iso: string): number {
    return Math.round((new Date(iso).getTime() - Date.now()) / 1000);
}
