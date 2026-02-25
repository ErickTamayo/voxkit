import { createContext, useContext } from "react";
import type { DragControls, MotionValue } from "motion/react";

interface ModalRootContextValue {
    isDesktopViewport: boolean;
    onOpenChange: (open: boolean) => void;
    open: boolean;
    sheetDragControls: DragControls;
    sheetDragY: MotionValue<number>;
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
