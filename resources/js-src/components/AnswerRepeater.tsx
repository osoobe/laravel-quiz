import { Plus, X } from 'lucide-react';
import type { QuestionType } from '../api/types';

export interface AnswerDraft {
    id?: string;
    text: string;
    is_correct: boolean;
}

export function AnswerRepeater({
    answers,
    questionType,
    onChange,
}: {
    answers: AnswerDraft[];
    questionType: QuestionType;
    onChange: (answers: AnswerDraft[]) => void;
}) {
    function setCorrect(index: number, checked: boolean) {
        if (questionType === 'radio') {
            onChange(answers.map((answer, i) => ({ ...answer, is_correct: i === index ? checked : false })));
        } else {
            onChange(answers.map((answer, i) => (i === index ? { ...answer, is_correct: checked } : answer)));
        }
    }

    function setText(index: number, text: string) {
        onChange(answers.map((answer, i) => (i === index ? { ...answer, text } : answer)));
    }

    function addAnswer() {
        onChange([...answers, { text: '', is_correct: false }]);
    }

    function removeAnswer(index: number) {
        onChange(answers.filter((_, i) => i !== index));
    }

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-900">Answers *</span>
                <button
                    type="button"
                    onClick={addAnswer}
                    className="inline-flex items-center gap-1 text-sm font-medium text-quiz-primary hover:underline"
                >
                    <Plus className="h-4 w-4" aria-hidden />
                    Add Answer
                </button>
            </div>

            <div className="space-y-2">
                {answers.map((answer, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <input
                            type={questionType === 'radio' ? 'radio' : 'checkbox'}
                            name="answer-correct"
                            checked={answer.is_correct}
                            onChange={(e) => setCorrect(index, e.target.checked)}
                            aria-label={`Mark answer ${index + 1} as correct`}
                            className="h-4 w-4 accent-[--color-quiz-primary]"
                        />
                        <input
                            type="text"
                            value={answer.text}
                            onChange={(e) => setText(index, e.target.value)}
                            placeholder={`Answer ${index + 1}`}
                            className="flex-1 rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                        />
                        {index >= 2 && (
                            <button
                                type="button"
                                onClick={() => removeAnswer(index)}
                                aria-label={`Remove answer ${index + 1}`}
                                className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-500"
                            >
                                <X className="h-4 w-4" aria-hidden />
                            </button>
                        )}
                    </div>
                ))}
            </div>

            <p className="text-xs text-quiz-muted">
                Check the box next to correct answer(s). At least one answer must be marked as correct.
            </p>
        </div>
    );
}
