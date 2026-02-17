import { CombinedGraphQLErrors } from "@apollo/client/errors";

function includesUnauthenticated(text: string | undefined): boolean {
    if (typeof text !== "string") {
        return false;
    }

    return text.toLowerCase().includes("unauthenticated");
}

function hasAuthStatusCode(value: unknown): boolean {
    if (typeof value !== "object" || value === null) {
        return false;
    }

    const errorLike = value as {
        response?: { status?: number };
        status?: number;
        statusCode?: number;
    };

    return errorLike.status === 401
        || errorLike.status === 419
        || errorLike.statusCode === 401
        || errorLike.statusCode === 419
        || errorLike.response?.status === 401
        || errorLike.response?.status === 419;
}

export function isUnauthenticatedError(error: unknown): boolean {
    if (CombinedGraphQLErrors.is(error)) {
        return error.errors.some((entry) => includesUnauthenticated(entry.message));
    }

    if (error instanceof Error && includesUnauthenticated(error.message)) {
        return true;
    }

    return hasAuthStatusCode(error);
}
