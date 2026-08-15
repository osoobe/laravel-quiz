export function Progress({ value }: { value: number }) {
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow={value} aria-valuemin={0} aria-valuemax={100}>
            <div className="h-full bg-quiz-primary transition-all" style={{ width: `${Math.min(100, Math.max(0, value))}%` }} />
        </div>
    );
}
