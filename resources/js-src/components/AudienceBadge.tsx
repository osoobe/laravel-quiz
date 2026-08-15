import { Badge } from './ui/Badge';
import type { Audience } from '../api/types';

export function audienceLabel(audience: Audience, isScoped: boolean): string {
    if (isScoped) return 'Hackathon';

    switch (audience) {
        case 'everyone':
            return 'Everyone';
        case 'logged_in':
            return 'Logged-in only';
        case 'private':
            return 'Private';
        default:
            return audience;
    }
}

export function AudienceBadge({ audience, isScoped = false }: { audience: Audience; isScoped?: boolean }) {
    return <Badge variant="outline">{audienceLabel(audience, isScoped)}</Badge>;
}
