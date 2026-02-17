import { beforeEach, describe, expect, it, vi } from "vitest";
import {
    redirectToSignInFromAuthError,
    resetAuthRedirectStateForTests,
} from "@/lib/authRedirect";

describe("authRedirect", () => {
    beforeEach(() => {
        resetAuthRedirectStateForTests();
        vi.useRealTimers();
        window.history.replaceState(window.history.state, "", "/");
    });

    it("redirects unauthenticated requests to signin when on protected routes", () => {
        window.history.replaceState(window.history.state, "", "/account");

        redirectToSignInFromAuthError();

        expect(window.location.pathname).toBe("/signin");
    });

    it("does not redirect when already on signin", () => {
        window.history.replaceState(window.history.state, "", "/signin");

        redirectToSignInFromAuthError();

        expect(window.location.pathname).toBe("/signin");
    });

    it("does not redirect when on a public route", () => {
        window.history.replaceState(window.history.state, "", "/");

        redirectToSignInFromAuthError();

        expect(window.location.pathname).toBe("/");
    });
});
