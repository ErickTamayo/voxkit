import { shouldUseTokenAuth } from "@/lib/authSession";

const LOCALHOST_ALIASES = new Set(["localhost", "127.0.0.1", "::1", "[::1]"]);
const DEFAULT_API_URL = "http://localhost:8000";
const API_BASE_URL = resolveApiBaseUrl(import.meta.env.VITE_API_URL);
let csrfCookieRequestPromise: Promise<void> | null = null;
let csrfCookieInitialized = false;

type EnsureSessionCsrfCookieOptions = {
    forceRefresh?: boolean;
};

function isLocalhostAlias(hostname: string): boolean {
    return LOCALHOST_ALIASES.has(hostname.toLowerCase());
}

function resolveApiBaseUrl(apiBaseUrl?: string): string {
    if (typeof window === "undefined") {
        return apiBaseUrl ?? DEFAULT_API_URL;
    }

    const resolvedApiBaseUrl = typeof apiBaseUrl === "string" && apiBaseUrl.trim() !== ""
        ? apiBaseUrl
        : DEFAULT_API_URL;

    try {
        const apiUrl = new URL(resolvedApiBaseUrl);

        if (
            isLocalhostAlias(apiUrl.hostname)
            && isLocalhostAlias(window.location.hostname)
            && apiUrl.hostname !== window.location.hostname
        ) {
            apiUrl.hostname = window.location.hostname;
        }

        return apiUrl.toString().replace(/\/+$/, "");
    } catch {
        return resolvedApiBaseUrl.replace(/\/+$/, "");
    }
}

export async function ensureSessionCsrfCookie(options?: EnsureSessionCsrfCookieOptions): Promise<void> {
    if (shouldUseTokenAuth()) {
        return;
    }

    if (!options?.forceRefresh && csrfCookieInitialized) {
        return;
    }

    if (csrfCookieRequestPromise !== null) {
        await csrfCookieRequestPromise;

        return;
    }

    csrfCookieRequestPromise = fetchSessionCsrfCookie();

    try {
        await csrfCookieRequestPromise;
    } finally {
        csrfCookieRequestPromise = null;
    }
}

async function fetchSessionCsrfCookie(): Promise<void> {
    const response = await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
        credentials: "include",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to initialize CSRF cookie (${response.status}).`);
    }

    csrfCookieInitialized = true;
}
