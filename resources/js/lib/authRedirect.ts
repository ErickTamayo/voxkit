import { isPrivateRoutePath } from "@/lib/privateRoutes";

const REDIRECT_COOLDOWN_MS = 500;

let isRedirectInFlight = false;
let lastRedirectAt = 0;

export function redirectToSignInFromAuthError(): void {
    const now = Date.now();
    const currentPathname = window.location.pathname;

    if (!isPrivateRoutePath(currentPathname)) {
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
