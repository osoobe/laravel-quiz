export function ItemCodeField({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-900">Item Code</label>
            <input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder="Auto-generated if left blank"
                className="w-full rounded-lg border border-quiz-border px-3 py-2 font-mono text-sm focus:border-quiz-primary focus:outline-none"
            />
            <p className="mt-1 text-xs text-quiz-muted">
                6+ letters, numbers, dashes, or underscores. Used to match this record on import/export.
            </p>
        </div>
    );
}
