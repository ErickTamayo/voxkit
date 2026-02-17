import { afterAll, afterEach, beforeAll } from "vitest";
import { server } from "@/tests/msw/server";

function clearCookies(): void {
    const cookieNames = document.cookie
        .split(";")
        .map((cookie) => cookie.trim())
        .map((cookie) => cookie.split("=")[0])
        .filter((name) => name !== "");

    for (const name of cookieNames) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
    }
}

beforeAll(() => {
    server.listen({ onUnhandledRequest: "error" });
});

afterEach(() => {
    server.resetHandlers();
    clearCookies();
    window.localStorage.clear();
});

afterAll(() => {
    server.close();
});
