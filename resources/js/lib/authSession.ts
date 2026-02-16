import { Capacitor } from "@capacitor/core";

const AUTH_TOKEN_STORAGE_KEY = "auth_token";

function hasWindow(): boolean {
    return typeof window !== "undefined";
}

export function shouldUseTokenAuth(): boolean {
    if (!hasWindow()) {
        return false;
    }

    return Capacitor.isNativePlatform();
}

export function readAuthToken(): string | null {
    if (!hasWindow()) {
        return null;
    }

    return window.localStorage.getItem(AUTH_TOKEN_STORAGE_KEY);
}

export function writeAuthToken(token: string): void {
    if (!hasWindow()) {
        return;
    }

    window.localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, token);
}

export function clearAuthToken(): void {
    if (!hasWindow()) {
        return;
    }

    window.localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
}
