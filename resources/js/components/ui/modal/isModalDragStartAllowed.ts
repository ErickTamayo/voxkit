const MODAL_NO_DRAG_SELECTOR = [
    "button",
    "a[href]",
    "input",
    "textarea",
    "select",
    "option",
    "label",
    "[role='button']",
    "[role='link']",
    "[role='checkbox']",
    "[role='menuitem']",
    "[role='switch']",
    "[contenteditable='']",
    "[contenteditable='true']",
    "[data-modal-no-drag]",
    "[data-slot='modal-handle-row']",
].join(",");

function getTargetElement(target: EventTarget | null): Element | null {
    if (target instanceof Element) {
        return target;
    }

    if (target instanceof Node) {
        return target.parentElement;
    }

    return null;
}

function isModalDragStartAllowed(target: EventTarget | null): boolean {
    const targetElement = getTargetElement(target);

    if (targetElement === null) {
        return false;
    }

    return targetElement.closest(MODAL_NO_DRAG_SELECTOR) === null;
}

export { isModalDragStartAllowed };
