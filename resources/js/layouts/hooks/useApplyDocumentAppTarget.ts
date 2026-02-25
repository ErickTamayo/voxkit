import { useEffect } from "react";

function restoreAttributeValue(
    element: Element,
    attributeName: string,
    previousValue: string | null,
): void {
    if (previousValue === null) {
        element.removeAttribute(attributeName);
        return;
    }

    element.setAttribute(attributeName, previousValue);
}

function useApplyDocumentAppTarget(target: "capacitor"): void {
    useEffect(() => {
        if (typeof document === "undefined") {
            return;
        }

        const html = document.documentElement;
        const body = document.body;
        const previousHtmlTarget = html.getAttribute("data-app-target");
        const previousBodyTarget = body.getAttribute("data-app-target");

        html.setAttribute("data-app-target", target);
        body.setAttribute("data-app-target", target);

        return () => {
            restoreAttributeValue(html, "data-app-target", previousHtmlTarget);
            restoreAttributeValue(body, "data-app-target", previousBodyTarget);
        };
    }, [target]);
}

export { useApplyDocumentAppTarget };
