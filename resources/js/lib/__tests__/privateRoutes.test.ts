import { beforeEach, describe, expect, it } from "vitest";
import {
    isPrivateRoutePath,
    registerPrivateRoutePattern,
    resetPrivateRoutePatternsForTests,
    unregisterPrivateRoutePattern,
} from "@/lib/privateRoutes";

describe("privateRoutes", () => {
    beforeEach(() => {
        resetPrivateRoutePatternsForTests();
    });

    it("matches exact registered routes", () => {
        registerPrivateRoutePattern("/account");

        expect(isPrivateRoutePath("/account")).toBe(true);
        expect(isPrivateRoutePath("/")).toBe(false);
    });

    it("matches route parameters and wildcard patterns", () => {
        registerPrivateRoutePattern("/account/:id");
        registerPrivateRoutePattern("/admin/*");

        expect(isPrivateRoutePath("/account/123")).toBe(true);
        expect(isPrivateRoutePath("/account/123/settings")).toBe(false);
        expect(isPrivateRoutePath("/admin/users/1")).toBe(true);
    });

    it("tracks registrations and unregistrations with map ref counting", () => {
        registerPrivateRoutePattern("/account");
        registerPrivateRoutePattern("/account");

        unregisterPrivateRoutePattern("/account");
        expect(isPrivateRoutePath("/account")).toBe(true);

        unregisterPrivateRoutePattern("/account");
        expect(isPrivateRoutePath("/account")).toBe(false);
    });
});
