const AUTH_ROUTE_PATHS = ["/signin"];
const AUTH_ROUTE_PREFIXES = ["/verify/"];
const PROTECTED_ROUTE_PATHS = ["/account"];
const REDIRECT_COOLDOWN_MS = 500;

let isRedirectInFlight = false;
let lastRedirectAt = 0;

function isAuthRoutePath(pathname: string): boolean {
    if (AUTH_ROUTE_PATHS.includes(pathname)) {
        return true;
    }

    return AUTH_ROUTE_PREFIXES.some((prefix) => pathname.startsWith(prefix));
}

function isProtectedRoutePath(pathname: string): boolean {
    return PROTECTED_ROUTE_PATHS.some((path) => pathname === path || pathname.startsWith(`${path}/`));
}

export function redirectToSignInFromAuthError(): void {
    const now = Date.now();
    const currentPathname = window.location.pathname;

    if (isAuthRoutePath(currentPathname)) {
        return;
    }

    if (!isProtectedRoutePath(currentPathname)) {
        return;
    }

    if (isRedirectInFlight || now - lastRedirectAt < REDIRECT_COOLDOWN_MS) {
        return;
    }

    isRedirectInFlight = true;
    lastRedirectAt = now;
    window.history.replaceState(window.history.state, "", "/signin");
    window.dispatchEvent(new PopStateEvent("popstate"));
    window.setTimeout(() => {
        isRedirectInFlight = false;
    }, REDIRECT_COOLDOWN_MS);
}

export function resetAuthRedirectStateForTests(): void {
    isRedirectInFlight = false;
    lastRedirectAt = 0;
}
