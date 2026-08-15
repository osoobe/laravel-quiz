import { Pencil } from 'lucide-react';
import { Card } from './ui/Card';
import { Badge } from './ui/Badge';
import { DifficultyBadge } from './DifficultyBadge';
import { ConfirmDelete } from './ConfirmDelete';
import { ItemCodeBadge } from './ItemCodeBadge';
import { cn } from '../lib/cn';
import type { Question } from '../api/types';

export function QuestionCard({
    question,
    onEdit,
    onDelete,
}: {
    question: Question;
    onEdit: () => void;
    onDelete: () => Promise<void> | void;
}) {
    return (
        <Card>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="flex items-center gap-2">
                        <p className="font-semibold text-gray-900">{question.question}</p>
                        <ItemCodeBadge code={question.itemcode} />
                    </div>
                    {question.description && <p className="mt-0.5 text-sm text-quiz-muted">{question.description}</p>}
                </div>
                <div className="flex shrink-0 items-center gap-1">
                    <button type="button" onClick={onEdit} aria-label="Edit question" className="rounded p-1.5 text-gray-500 hover:bg-gray-100">
                        <Pencil className="h-4 w-4" aria-hidden />
                    </button>
                    <ConfirmDelete label="Delete question" description={`Delete "${question.question}"?`} onConfirm={onDelete} />
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-1.5">
                <DifficultyBadge difficulty={question.difficulty} />
                <Badge variant="outline">{question.question_type === 'radio' ? 'Single Answer' : 'Multiple Answers'}</Badge>
                {question.category && <Badge className="bg-quiz-accent text-quiz-primary">{question.category.name}</Badge>}
            </div>

            <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                {question.answers.map((answer) => (
                    <div
                        key={answer.id}
                        className={cn(
                            'rounded-lg px-3 py-2 text-sm',
                            answer.is_correct ? 'bg-green-50 text-green-800' : 'bg-gray-50 text-gray-700',
                        )}
                    >
                        {answer.text}
                    </div>
                ))}
            </div>
        </Card>
    );
}
