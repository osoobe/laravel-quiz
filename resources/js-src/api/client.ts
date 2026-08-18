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

function performRequest(path: string, options: RequestInit, method: string): Promise<Response> {
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');

    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const xsrfToken = getCookie('XSRF-TOKEN');

    if (xsrfToken) {
        headers.set('X-XSRF-TOKEN', xsrfToken);
    }

    return fetch(`${window.QuizConfig.apiBase}${path}`, {
        ...options,
        method,
        headers,
        credentials: 'include',
    });
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
    const method = (options.method ?? 'GET').toUpperCase();
    const isMutating = method !== 'GET' && method !== 'HEAD';

    if (isMutating) {
        await ensureCsrfCookie();
    }

    let response = await performRequest(path, options, method);

    // A memoized CSRF cookie can go stale while the SPA sits open (idle session
    // lifetime, session eviction, etc.) since tab navigation never reloads the page
    // to re-prime it. Re-prime once and retry before giving up.
    if (isMutating && response.status === 419) {
        csrfCookieReady = null;
        await ensureCsrfCookie();
        response = await performRequest(path, options, method);
    }

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
