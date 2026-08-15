import { Badge } from './ui/Badge';
import type { Difficulty } from '../api/types';

const colors: Record<Difficulty, string> = {
    easy: 'bg-green-50 text-green-700',
    medium: 'bg-amber-50 text-amber-700',
    hard: 'bg-orange-50 text-orange-700',
    expert: 'bg-red-50 text-red-700',
};

export function DifficultyBadge({ difficulty }: { difficulty: Difficulty | null }) {
    if (!difficulty) {
        return <Badge variant="outline">Mixed</Badge>;
    }

    return <Badge className={colors[difficulty]}>{difficulty}</Badge>;
}
