import { Medal, Trophy } from 'lucide-react';

export function RankIcon({ rank }: { rank: number }) {
    if (rank === 1) return <Trophy className="h-5 w-5 text-quiz-primary" aria-hidden />;
    if (rank === 2) return <Medal className="h-5 w-5 text-gray-400" aria-hidden />;
    if (rank === 3) return <Medal className="h-5 w-5 text-quiz-primary/70" aria-hidden />;

    return <span className="w-5 text-center text-sm font-semibold text-quiz-muted">{rank}</span>;
}
