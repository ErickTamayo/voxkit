import { HttpResponse, http } from "msw";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { server } from "@/tests/msw/server";

type GraphQLOperationPayload = {
    operationName?: string;
};

function responseForOperation(operationName?: string): Record<string, unknown> {
    if (operationName === "Me") {
        return {
            data: {
                me: {
                    __typename: "User",
                    id: "user_1",
                    name: "Test User",
                    email: "test@example.com",
                    email_verified_at: null,
                },
            },
        };
    }

    return {
        errors: [
            {
                message: `Unexpected GraphQL operation: ${operationName ?? "unknown"}`,
            },
        ],
    };
}

async function resolveGraphQlResponse(request: Request): Promise<Response> {
    const payload = await request.json();

    if (Array.isArray(payload)) {
        return HttpResponse.json(
            payload.map((entry) => responseForOperation((entry as GraphQLOperationPayload).operationName)),
        );
    }

    return HttpResponse.json(responseForOperation((payload as GraphQLOperationPayload).operationName));
}

describe("apolloClient transport", () => {
    beforeEach(() => {
        vi.resetModules();
    });

    it("initializes csrf cookie once and forwards xsrf token header for session auth requests", async () => {
        let csrfCookieRequestCount = 0;
        let graphQlRequestCount = 0;
        const authorizationHeaders: Array<string | null> = [];
        const xsrfHeaders: Array<string | null> = [];

        server.use(
            http.get("http://localhost:8000/sanctum/csrf-cookie", () => {
                csrfCookieRequestCount += 1;

                return new HttpResponse(null, { status: 204 });
            }),
            http.post("http://localhost:8000/graphql", async ({ request }) => {
                graphQlRequestCount += 1;
                authorizationHeaders.push(request.headers.get("authorization"));
                xsrfHeaders.push(request.headers.get("x-xsrf-token"));

                return resolveGraphQlResponse(request);
            }),
        );

        document.cookie = "XSRF-TOKEN=csrf-session-token; path=/";

        const [{ apolloClient }, { MeDocument }, { Capacitor }] = await Promise.all([
            import("@/lib/apolloClient"),
            import("@/hooks/useUser.graphql.ts"),
            import("@capacitor/core"),
        ]);
        vi.spyOn(Capacitor, "isNativePlatform").mockReturnValue(false);

        await apolloClient.query({
            fetchPolicy: "network-only",
            query: MeDocument,
        });
        await apolloClient.query({
            fetchPolicy: "network-only",
            query: MeDocument,
        });

        expect(csrfCookieRequestCount).toBe(1);
        expect(graphQlRequestCount).toBe(2);
        expect(authorizationHeaders).toEqual([null, null]);
        expect(xsrfHeaders).toEqual(["csrf-session-token", "csrf-session-token"]);
    });

    it("uses bearer token transport and still initializes csrf cookie on native auth mode", async () => {
        let csrfCookieRequestCount = 0;
        let graphQlRequestCount = 0;
        const authorizationHeaders: Array<string | null> = [];
        const xsrfHeaders: Array<string | null> = [];

        server.use(
            http.get("http://localhost:8000/sanctum/csrf-cookie", () => {
                csrfCookieRequestCount += 1;

                return new HttpResponse(null, { status: 204 });
            }),
            http.post("http://localhost:8000/graphql", async ({ request }) => {
                graphQlRequestCount += 1;
                authorizationHeaders.push(request.headers.get("authorization"));
                xsrfHeaders.push(request.headers.get("x-xsrf-token"));

                return resolveGraphQlResponse(request);
            }),
        );

        document.cookie = "XSRF-TOKEN=native-csrf-token; path=/";

        const [{ Capacitor }, { writeAuthToken }] = await Promise.all([
            import("@capacitor/core"),
            import("@/lib/authSession"),
        ]);
        vi.spyOn(Capacitor, "isNativePlatform").mockReturnValue(true);
        await writeAuthToken("native-token-123");

        const [{ apolloClient }, { MeDocument }] = await Promise.all([
            import("@/lib/apolloClient"),
            import("@/hooks/useUser.graphql.ts"),
        ]);

        await apolloClient.query({
            fetchPolicy: "network-only",
            query: MeDocument,
        });

        expect(csrfCookieRequestCount).toBe(1);
        expect(graphQlRequestCount).toBe(1);
        expect(authorizationHeaders).toEqual(["Bearer native-token-123"]);
        expect(xsrfHeaders).toEqual(["native-csrf-token"]);
    });
});
