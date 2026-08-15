export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errorCode: string | null = null,
        public errors: Record<string, string[]> | null = null,
    ) {
        super(message);
    }
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

let csrfCookieReady: Promise<void> | null = null;

function ensureCsrfCookie(): Promise<void> {
    csrfCookieReady ??= fetch('/sanctum/csrf-cookie', { credentials: 'include' }).then(() => undefined);

    return csrfCookieReady;
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
    const method = (options.method ?? 'GET').toUpperCase();

    if (method !== 'GET' && method !== 'HEAD') {
        await ensureCsrfCookie();
    }

    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');

    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const xsrfToken = getCookie('XSRF-TOKEN');

    if (xsrfToken) {
        headers.set('X-XSRF-TOKEN', xsrfToken);
    }

    const response = await fetch(`${window.QuizConfig.apiBase}${path}`, {
        ...options,
        method,
        headers,
        credentials: 'include',
    });

    if (!response.ok) {
        let payload: { message?: string; error_code?: string; errors?: Record<string, string[]> } | null = null;

        try {
            payload = await response.json();
        } catch {
            // non-JSON error body — fall through with a generic message
        }

        throw new ApiError(
            response.status,
            payload?.message ?? `Request failed with status ${response.status}`,
            payload?.error_code ?? null,
            payload?.errors ?? null,
        );
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return response.json() as Promise<T>;
}
