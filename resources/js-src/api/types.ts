export interface QuizConfig {
    csrfToken: string;
    apiBase: string;
    basePath: string;
    user: { id: string | number; name: string; avatarUrl: string | null; isStaff: boolean } | null;
    loginUrl: string | null;
    /** Mirrors the host's own flash session keys (`message`/`error`/`bulk_errors`) so a redirect into the SPA still shows its flash. */
    flash: { message: string | null; error: string | null; bulk_errors: string[] | null };
}

declare global {
    interface Window {
        QuizConfig: QuizConfig;
    }
}

export type Difficulty = 'easy' | 'medium' | 'hard' | 'expert';
export type QuestionType = 'radio' | 'checkbox';
export type Audience = 'everyone' | 'logged_in' | 'private' | string;
export type AttemptStatus = 'in_progress' | 'completed' | 'abandoned';

export interface Paginated<T> {
    data: T[];
    meta?: { current_page: number; last_page: number; total: number };
}

export interface Topic {
    id: string;
    itemcode: string | null;
    name: string;
    description: string | null;
    is_active: boolean;
}

export interface Category {
    id: string;
    itemcode: string | null;
    name: string;
    description: string | null;
    is_active: boolean;
}

export interface Answer {
    id: string;
    text: string;
    is_correct?: boolean;
}

export interface Question {
    id: string;
    itemcode: string | null;
    question: string;
    description: string | null;
    topic?: Topic | null;
    category?: Category | null;
    difficulty: Difficulty;
    question_type: QuestionType;
    answers: Answer[];
    is_active: boolean;
}

export interface Quiz {
    id: string;
    itemcode: string | null;
    name: string;
    description: string | null;
    topic_ids: string[];
    category_ids: string[];
    difficulty: Difficulty | null;
    question_count: number;
    randomize_questions: boolean;
    time_limit_minutes: number | null;
    max_attempts: number;
    is_active: boolean;
    audience: Audience;
    is_scoped: boolean;
    created_by: string;
    created_at: string;
}

export interface CatalogueQuiz {
    id: string;
    name: string;
    description: string | null;
    question_count: number;
    time_limit_minutes: number | null;
    difficulty: Difficulty | null;
    topic: string | null;
    audience: Audience;
    max_attempts: number;
}

export interface TakerAttempt {
    id: string;
    started_at: string;
    expires_at: string | null;
    answers: Record<string, string[]>;
}

export interface TakerPayload {
    quiz: Quiz;
    attempt: TakerAttempt;
    questions: Question[];
}

export interface ShowPayload {
    quiz: Quiz;
    attempt: TakerAttempt | null;
    attempts_used: number;
}

export interface SubmitResult {
    score: number;
    correct_answers: number;
    total_questions: number;
    status: AttemptStatus;
}

export interface LeaderboardEntry {
    user: { name: string; avatar_url: string | null };
    score: number;
    correct_answers: number;
    total_questions: number;
    completed_at: string;
}

export interface ResultsAttempt {
    id: string;
    user: { id: string | number; name: string; avatar_url: string | null };
    started_at: string;
    completed_at: string | null;
    correct_answers: number | null;
    total_questions: number | null;
    score: number | null;
    status: AttemptStatus;
}

export interface Invitation {
    id: string;
    user: { id: string | number; name: string; email: string | null };
    invited_at: string;
}

export interface QuestionPayload {
    itemcode?: string | null;
    question: string;
    description: string | null;
    topic_id: string | null;
    category_id: string | null;
    difficulty: Difficulty;
    question_type: QuestionType;
    answers: Array<{ id?: string; text: string; is_correct: boolean }>;
}

export interface ImportSummary {
    imported: number;
    failed: number;
    errors: { row: number; message: string }[];
}

export interface InviteSummary {
    invited: number;
    already_invited: number;
    not_found: number;
    failed: number;
}

export interface ApiErrorPayload {
    message: string;
    error_code?: string;
    errors?: Record<string, string[]>;
}
