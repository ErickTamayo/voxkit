import { useEffect, useState } from "react";

// Matches Tailwind default `md` breakpoint (48rem / 768px).
// If Tailwind breakpoints are customized, update this query.
const DESKTOP_VIEWPORT_MEDIA_QUERY = "(min-width: 48rem)";

function useIsDesktopViewport(): boolean {
    const [matches, setMatches] = useState<boolean>(() => {
        if (typeof window === "undefined") {
            return false;
        }

        return window.matchMedia(DESKTOP_VIEWPORT_MEDIA_QUERY).matches;
    });

    useEffect(() => {
        if (typeof window === "undefined") {
            return;
        }

        const mediaQuery = window.matchMedia(DESKTOP_VIEWPORT_MEDIA_QUERY);
        const update = (): void => {
            setMatches(mediaQuery.matches);
        };

        update();
        mediaQuery.addEventListener("change", update);

        return () => {
            mediaQuery.removeEventListener("change", update);
        };
    }, []);

    return matches;
}

export { useIsDesktopViewport };
