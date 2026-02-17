import { beforeEach, describe, expect, it, vi } from "vitest";

const clearSecureStorageTokenMock = vi.fn();
const isNativePlatformMock = vi.fn();
const readSecureStorageTokenMock = vi.fn();
const writeSecureStorageTokenMock = vi.fn();

vi.mock("@capacitor/core", () => ({
    Capacitor: {
        isNativePlatform: () => isNativePlatformMock(),
    },
}));

vi.mock("capacitor-secure-storage-plugin", () => ({
    SecureStoragePlugin: {
        get: (options: { key: string }) => readSecureStorageTokenMock(options),
        set: (options: { key: string; value: string }) => writeSecureStorageTokenMock(options),
        remove: (options: { key: string }) => clearSecureStorageTokenMock(options),
    },
}));

describe("authSession", () => {
    beforeEach(() => {
        vi.resetModules();
        clearSecureStorageTokenMock.mockReset();
        isNativePlatformMock.mockReset();
        readSecureStorageTokenMock.mockReset();
        writeSecureStorageTokenMock.mockReset();
        window.localStorage.clear();
    });

    it("uses session-only auth on web", async () => {
        isNativePlatformMock.mockReturnValue(false);
        window.localStorage.setItem("auth_token", "legacy-token");
        window.localStorage.setItem("cap_sec_auth_token", "legacy-secure-token");

        const { shouldUseTokenAuth, readAuthToken, writeAuthToken, clearAuthToken } = await import("@/lib/authSession");

        expect(shouldUseTokenAuth()).toBe(false);
        expect(await readAuthToken()).toBeNull();
        await writeAuthToken("web-token");
        await clearAuthToken();

        expect(readSecureStorageTokenMock).not.toHaveBeenCalled();
        expect(writeSecureStorageTokenMock).not.toHaveBeenCalled();
        expect(clearSecureStorageTokenMock).not.toHaveBeenCalled();
        expect(window.localStorage.getItem("auth_token")).toBe("legacy-token");
        expect(window.localStorage.getItem("cap_sec_auth_token")).toBe("legacy-secure-token");
    });

    it("uses secure storage token auth on native platforms", async () => {
        isNativePlatformMock.mockReturnValue(true);
        writeSecureStorageTokenMock.mockResolvedValue({ value: true });
        readSecureStorageTokenMock.mockResolvedValue({ value: "native-token" });
        clearSecureStorageTokenMock.mockResolvedValue({ value: true });

        const { shouldUseTokenAuth, readAuthToken, writeAuthToken, clearAuthToken } = await import("@/lib/authSession");

        expect(shouldUseTokenAuth()).toBe(true);
        await writeAuthToken("native-token");
        expect(writeSecureStorageTokenMock).toHaveBeenCalledWith({
            key: "auth_token",
            value: "native-token",
        });

        expect(await readAuthToken()).toBe("native-token");

        await clearAuthToken();
        expect(clearSecureStorageTokenMock).toHaveBeenCalledWith({
            key: "auth_token",
        });
    });

    it("returns null when secure storage token is missing", async () => {
        isNativePlatformMock.mockReturnValue(true);
        readSecureStorageTokenMock.mockRejectedValue(new Error("missing"));

        const { readAuthToken } = await import("@/lib/authSession");

        expect(await readAuthToken()).toBeNull();
    });
});
