import { describe, expect, it } from "vitest";
import { isUnauthenticatedError } from "@/lib/authErrors";

describe("authErrors", () => {
    it("detects unauthenticated error messages", () => {
        expect(isUnauthenticatedError(new Error("Unauthenticated."))).toBe(true);
    });

    it("detects unauthenticated status codes", () => {
        expect(isUnauthenticatedError({ statusCode: 401 })).toBe(true);
        expect(isUnauthenticatedError({ response: { status: 419 } })).toBe(true);
    });

    it("ignores non-auth errors", () => {
        expect(isUnauthenticatedError(new Error("Something else"))).toBe(false);
        expect(isUnauthenticatedError({ statusCode: 500 })).toBe(false);
    });
});
