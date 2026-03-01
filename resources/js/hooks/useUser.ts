import { useSuspenseQuery } from "@apollo/client/react";
import type { MeQuery } from "@/hooks/useUser.graphql.ts";
import { MeDocument } from "@/hooks/useUser.graphql.ts";

export function useUser(): {
    user: MeQuery["me"];
} {
    console.count("useUser-query");
    const { data } = useSuspenseQuery(MeDocument);

    if (data.me === null || data.me === undefined) {
        throw new Error("Expected authenticated user data.");
    }

    return {
        user: data.me,
    };
}
