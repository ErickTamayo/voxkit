import { ApolloClient, InMemoryCache } from "@apollo/client";
import { BatchHttpLink } from "@apollo/client/link/batch-http";
import { SetContextLink } from "@apollo/client/link/context";
import { ErrorLink } from "@apollo/client/link/error";
import { clearAuthToken, readAuthToken } from "@/lib/authSession";
import { isUnauthenticatedError } from "@/lib/authErrors";
import { redirectToSignInFromAuthError } from "@/lib/authRedirect";
import { ensureSessionCsrfCookie } from "@/lib/csrf";
import { setSessionUnauthenticated } from "@/stores/sessionStore";

const LOCALHOST_ALIASES = new Set(["localhost", "127.0.0.1", "::1", "[::1]"]);
const DEFAULT_API_URL = "http://localhost:8000";
const API_URL = resolveApiBaseUrl(import.meta.env.VITE_API_URL);

function isLocalhostAlias(hostname: string): boolean {
    return LOCALHOST_ALIASES.has(hostname.toLowerCase());
}

function resolveApiBaseUrl(apiBaseUrl?: string): string {
    try {
        const resolvedApiUrl = typeof apiBaseUrl === "string" && apiBaseUrl.trim() !== ""
            ? apiBaseUrl
            : DEFAULT_API_URL;
        const parsedApiUrl = new URL(resolvedApiUrl);

        if (
            isLocalhostAlias(parsedApiUrl.hostname)
            && isLocalhostAlias(window.location.hostname)
            && parsedApiUrl.hostname !== window.location.hostname
        ) {
            parsedApiUrl.hostname = window.location.hostname;
        }

        return parsedApiUrl.toString().replace(/\/+$/, "");
    } catch {
        return (apiBaseUrl ?? DEFAULT_API_URL).replace(/\/+$/, "");
    }
}

function getXsrfToken(): string | null {
    const tokenCookie = document.cookie
        .split(";")
        .map((value) => value.trim())
        .find((value) => value.startsWith("XSRF-TOKEN="));

    if (tokenCookie === undefined) {
        return null;
    }

    const rawValue = tokenCookie.slice("XSRF-TOKEN=".length);
    return decodeURIComponent(rawValue);
}

const httpLink = new BatchHttpLink({
    uri: `${API_URL}/graphql`,
    credentials: "include",
    batchInterval: 20,
    batchMax: 10,
});

const AUTH_ERROR_IGNORED_OPERATIONS = new Set([
    "RequestAuthenticationCode",
    "AuthenticateWithCode",
    "RevokeToken",
]);

async function handleUnauthenticatedTransportError(): Promise<void> {
    await clearAuthToken();
    setSessionUnauthenticated();
    redirectToSignInFromAuthError();
}

const transportErrorLink = new ErrorLink(({ error, operation }) => {
    const operationName = operation.operationName;
    if (operationName !== undefined && AUTH_ERROR_IGNORED_OPERATIONS.has(operationName)) {
        return;
    }

    if (!isUnauthenticatedError(error)) {
        return;
    }

    void handleUnauthenticatedTransportError();
});

const authLink = new SetContextLink(async (prevContext) => {
    await ensureSessionCsrfCookie();
    const xsrfToken = getXsrfToken();
    const token = await readAuthToken();

    return {
        headers: {
            ...prevContext.headers,
            ...(xsrfToken !== null ? { "X-XSRF-TOKEN": xsrfToken } : {}),
            ...(token !== null ? { Authorization: `Bearer ${token}` } : {}),
        },
    };
});

export const apolloClient = new ApolloClient({
    link: authLink.concat(transportErrorLink).concat(httpLink),
    cache: new InMemoryCache(),
});
