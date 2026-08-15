import { cn } from '../lib/cn';

export function QuestionDots({
    count,
    current,
    answered,
    onJump,
}: {
    count: number;
    current: number;
    answered: boolean[];
    onJump: (index: number) => void;
}) {
    return (
        <ul className="flex items-center gap-1.5" aria-label="Question navigation">
            {Array.from({ length: count }).map((_, index) => (
                <li key={index}>
                    <button
                        type="button"
                        onClick={() => onJump(index)}
                        aria-label={`Go to question ${index + 1}`}
                        aria-current={index === current}
                        className={cn(
                            'h-2 w-2 rounded-full transition-opacity',
                            index === current
                                ? 'bg-quiz-primary'
                                : answered[index]
                                  ? 'bg-quiz-primary/50'
                                  : 'bg-gray-300',
                        )}
                    />
                </li>
            ))}
        </ul>
    );
}
