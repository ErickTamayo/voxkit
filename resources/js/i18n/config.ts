import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import en from "@/i18n/locales/en.json";

const resources = {
    en: {
        translation: en,
    },
} as const;

function resolveLanguageCode(): string {
    if (typeof navigator === "undefined") {
        return "en";
    }

    const preferredTag = navigator.languages[0] ?? navigator.language;
    const languageCode = preferredTag?.split("-")[0]?.toLowerCase();

    return languageCode === "en" ? languageCode : "en";
}

void i18n
    .use(initReactI18next)
    .init({
        resources,
        lng: resolveLanguageCode(),
        fallbackLng: "en",
        keySeparator: false,
        nsSeparator: false,
        returnNull: false,
        returnEmptyString: false,
        interpolation: {
            escapeValue: false,
        },
        debug: import.meta.env.DEV,
        react: {
            useSuspense: false,
        },
    });

export default i18n;
