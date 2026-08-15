import { apiFetch } from './client';
import type {
    CatalogueQuiz,
    Category,
    ImportSummary,
    Invitation,
    InviteSummary,
    Paginated,
    Question,
    QuestionPayload,
    Quiz,
    ResultsAttempt,
    ShowPayload,
    SubmitResult,
    TakerPayload,
    Topic,
} from './types';

export const QuizApi = {
    catalogue: () => apiFetch<Paginated<CatalogueQuiz>>('/quizzes'),
    show: (quizId: string) => apiFetch<ShowPayload>(`/quizzes/${quizId}`),

    startAttempt: (quizId: string) => apiFetch<TakerPayload>(`/quizzes/${quizId}/attempts`, { method: 'POST' }),
    autosave: (quizId: string, attemptId: string, answers: Record<string, string[]>) =>
        apiFetch<{ data: TakerPayload['attempt'] }>(`/quizzes/${quizId}/attempts/${attemptId}`, {
            method: 'PATCH',
            body: JSON.stringify({ answers }),
        }),
    submit: (quizId: string, attemptId: string, answers: Record<string, string[]>) =>
        apiFetch<SubmitResult>(`/quizzes/${quizId}/attempts/${attemptId}/submit`, {
            method: 'POST',
            body: JSON.stringify({ answers }),
        }),

    leaderboard: (quizId: string) =>
        apiFetch<{ quiz: { id: string; name: string }; entries: import('./types').LeaderboardEntry[] }>(
            `/quizzes/${quizId}/leaderboard`,
        ),

    results: (quizId: string) => apiFetch<Paginated<ResultsAttempt>>(`/quizzes/${quizId}/results`),
    deleteAttempt: (quizId: string, attemptId: string) =>
        apiFetch<void>(`/quizzes/${quizId}/results/${attemptId}`, { method: 'DELETE' }),
};

export const AdminQuizApi = {
    index: () => apiFetch<Paginated<Quiz>>('/admin/quizzes'),
    show: (id: string) => apiFetch<{ data: Quiz }>(`/admin/quizzes/${id}`),
    create: (payload: Partial<Quiz>) =>
        apiFetch<{ data: Quiz }>('/admin/quizzes', { method: 'POST', body: JSON.stringify(payload) }),
    update: (id: string, payload: Partial<Quiz>) =>
        apiFetch<{ data: Quiz }>(`/admin/quizzes/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    destroy: (id: string) => apiFetch<void>(`/admin/quizzes/${id}`, { method: 'DELETE' }),
    export: () => apiFetch<Record<string, unknown>[]>('/admin/quizzes-export'),
    import: (quizzes: Record<string, unknown>[]) =>
        apiFetch<ImportSummary>('/admin/quizzes-import', { method: 'POST', body: JSON.stringify({ quizzes }) }),
};

export const AdminQuestionApi = {
    index: (params: { search?: string; difficulty?: string } = {}) => {
        // new URLSearchParams() stringifies `undefined` values as the literal text
        // "undefined" rather than omitting them — filter those out first, or an
        // empty search/difficulty filter sends `search=undefined` and the backend
        // matches nothing.
        const present = Object.fromEntries(
            Object.entries(params).filter(([, value]) => value !== undefined && value !== ''),
        ) as Record<string, string>;
        const query = new URLSearchParams(present).toString();

        return apiFetch<Paginated<Question>>(`/admin/questions${query ? `?${query}` : ''}`);
    },
    create: (payload: QuestionPayload) =>
        apiFetch<{ data: Question }>('/admin/questions', { method: 'POST', body: JSON.stringify(payload) }),
    update: (id: string, payload: QuestionPayload) =>
        apiFetch<{ data: Question }>(`/admin/questions/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    destroy: (id: string) => apiFetch<void>(`/admin/questions/${id}`, { method: 'DELETE' }),
    export: () => apiFetch<Record<string, unknown>[]>('/admin/questions-export'),
    import: (questions: Record<string, unknown>[]) =>
        apiFetch<ImportSummary>('/admin/questions-import', { method: 'POST', body: JSON.stringify({ questions }) }),
};

export const AdminTopicApi = {
    index: () => apiFetch<Paginated<Topic>>('/admin/topics'),
    create: (payload: Partial<Topic>) =>
        apiFetch<{ data: Topic }>('/admin/topics', { method: 'POST', body: JSON.stringify(payload) }),
    update: (id: string, payload: Partial<Topic>) =>
        apiFetch<{ data: Topic }>(`/admin/topics/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    destroy: (id: string) => apiFetch<void>(`/admin/topics/${id}`, { method: 'DELETE' }),
    export: () => apiFetch<Record<string, unknown>[]>('/admin/topics-export'),
    import: (topics: Record<string, unknown>[]) =>
        apiFetch<ImportSummary>('/admin/topics-import', { method: 'POST', body: JSON.stringify({ topics }) }),
};

export const AdminCategoryApi = {
    index: () => apiFetch<Paginated<Category>>('/admin/categories'),
    create: (payload: Partial<Category>) =>
        apiFetch<{ data: Category }>('/admin/categories', { method: 'POST', body: JSON.stringify(payload) }),
    update: (id: string, payload: Partial<Category>) =>
        apiFetch<{ data: Category }>(`/admin/categories/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    destroy: (id: string) => apiFetch<void>(`/admin/categories/${id}`, { method: 'DELETE' }),
    export: () => apiFetch<Record<string, unknown>[]>('/admin/categories-export'),
    import: (categories: Record<string, unknown>[]) =>
        apiFetch<ImportSummary>('/admin/categories-import', { method: 'POST', body: JSON.stringify({ categories }) }),
};

export interface ExportAllBundle {
    topics: Record<string, unknown>[];
    categories: Record<string, unknown>[];
    questions: Record<string, unknown>[];
    quizzes: Record<string, unknown>[];
}

export const AdminDataApi = {
    exportAll: () => apiFetch<ExportAllBundle>('/admin/export-all'),
};

export const AdminInvitationApi = {
    index: (quizId: string) => apiFetch<Invitation[]>(`/admin/quizzes/${quizId}/invitations`),
    invite: (quizId: string, identifiers: string) =>
        apiFetch<InviteSummary>(`/admin/quizzes/${quizId}/invitations`, {
            method: 'POST',
            body: JSON.stringify({ identifiers }),
        }),
    remove: (quizId: string, invitationId: string) =>
        apiFetch<void>(`/admin/quizzes/${quizId}/invitations/${invitationId}`, { method: 'DELETE' }),
};
