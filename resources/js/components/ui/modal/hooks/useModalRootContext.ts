import { createContext, useContext } from "react";

interface ModalRootContextValue {
    isDesktopViewport: boolean;
    open: boolean;
}

const ModalRootContext = createContext<ModalRootContextValue | null>(null);

function useModalRootContext(componentName: string): ModalRootContextValue {
    const context = useContext(ModalRootContext);

    if (context === null) {
        throw new Error(`${componentName} must be used within Modal.Root`);
    }

    return context;
}

export { ModalRootContext, useModalRootContext };
export type { ModalRootContextValue };
