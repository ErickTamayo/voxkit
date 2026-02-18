import { useSuspenseQuery } from "@apollo/client/react";
import type { MeQuery } from "@/hooks/useUser.graphql.ts";
import { MeDocument } from "@/hooks/useUser.graphql.ts";

export function useUser(): {
    user: MeQuery["me"];
} {
    const { data } = useSuspenseQuery(MeDocument, {
        fetchPolicy: "network-only",
    });

    if (data.me === null || data.me === undefined) {
        throw new Error("Expected authenticated user data.");
    }

    return {
        user: data.me,
    };
}
