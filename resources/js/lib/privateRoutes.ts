import { parse as parsePattern } from "regexparam";
import { matchRoute } from "wouter";

const privateRouteMap = new Map<string, number>();

function normalizePath(path: string): string {
    const trimmedPath = path.trim();
    if (trimmedPath === "") {
        return "/";
    }

    const withoutTrailingSlashes = trimmedPath.replace(/\/+$/, "");
    return withoutTrailingSlashes === "" ? "/" : withoutTrailingSlashes;
}

export function registerPrivateRoutePattern(pattern: string): void {
    const normalizedPattern = normalizePath(pattern);
    const existingRegistrationCount = privateRouteMap.get(normalizedPattern) ?? 0;
    privateRouteMap.set(normalizedPattern, existingRegistrationCount + 1);
}

export function unregisterPrivateRoutePattern(pattern: string): void {
    const normalizedPattern = normalizePath(pattern);
    const existingRegistrationCount = privateRouteMap.get(normalizedPattern);
    if (existingRegistrationCount === undefined) {
        return;
    }

    const remainingRegistrations = existingRegistrationCount - 1;
    if (remainingRegistrations <= 0) {
        privateRouteMap.delete(normalizedPattern);

        return;
    }

    privateRouteMap.set(normalizedPattern, remainingRegistrations);
}

export function isPrivateRoutePath(path: string): boolean {
    const normalizedPath = normalizePath(path);
    for (const pattern of privateRouteMap.keys()) {
        const [isMatch] = matchRoute(parsePattern, pattern, normalizedPath);
        if (isMatch) {
            return true;
        }
    }

    return false;
}

export function resetPrivateRoutePatternsForTests(): void {
    privateRouteMap.clear();
}
