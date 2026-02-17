import { Capacitor } from "@capacitor/core";
import { SecureStoragePlugin } from "capacitor-secure-storage-plugin";

const AUTH_TOKEN_STORAGE_KEY = "auth_token";

export function shouldUseTokenAuth(): boolean {
    return Capacitor.isNativePlatform();
}

export async function readAuthToken(): Promise<string | null> {
    if (!shouldUseTokenAuth()) {
        return null;
    }

    try {
        const result = await SecureStoragePlugin.get({ key: AUTH_TOKEN_STORAGE_KEY });

        return result.value;
    } catch {
        return null;
    }
}

export async function writeAuthToken(token: string): Promise<void> {
    if (!shouldUseTokenAuth()) {
        return;
    }

    await SecureStoragePlugin.set({
        key: AUTH_TOKEN_STORAGE_KEY,
        value: token,
    });
}

export async function clearAuthToken(): Promise<void> {
    if (!shouldUseTokenAuth()) {
        return;
    }

    try {
        await SecureStoragePlugin.remove({ key: AUTH_TOKEN_STORAGE_KEY });
    } catch {
        // Ignore missing token errors to keep logout/idempotent clear flows resilient.
    }
}
