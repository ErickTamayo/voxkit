export type AppTarget = "web" | "capacitor";

export const appTarget: AppTarget = "capacitor";

export function isCapacitorTarget(): boolean {
    return appTarget === "capacitor";
}
