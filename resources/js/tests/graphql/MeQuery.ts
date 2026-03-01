import type { MockedResponse } from "@apollo/client/testing";
import {
    MeDocument,
    type MeQuery,
    type MeQueryVariables,
} from "@/hooks/useUser.graphql.ts";

interface MeQueryMockOverrides {
    maxUsageCount?: number;
    me?: Partial<NonNullable<MeQuery["me"]>>;
}

function meQuery(overrides: MeQueryMockOverrides = {}): MockedResponse<MeQuery, MeQueryVariables> {
    const baseUser: NonNullable<MeQuery["me"]> = {
        __typename: "User",
        id: "user-1",
        name: "Test User",
        email: "test@example.com",
        email_verified_at: null,
    };
    const nextUser = {
        ...baseUser,
        ...overrides.me,
    };

    return {
        request: {
            query: MeDocument,
            variables: (variables) => Object.keys(variables ?? {}).length === 0,
        },
        result: {
            data: {
                __typename: "Query",
                me: nextUser,
            },
        },
        maxUsageCount: overrides.maxUsageCount ?? 20,
    };
}

export { meQuery };
export type { MeQueryMockOverrides };
