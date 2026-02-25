export type AppTarget = "web" | "capacitor";

export const appTarget: AppTarget = "web";

export function isCapacitorTarget(): boolean {
    return appTarget === "capacitor";
}
