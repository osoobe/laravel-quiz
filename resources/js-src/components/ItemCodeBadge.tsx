export function ItemCodeBadge({ code }: { code: string | null }) {
    if (!code) return null;

    return <span className="font-mono text-xs text-quiz-muted">#{code}</span>;
}
