import { render } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { isModalDragStartAllowed } from "@/components/ui/modal/isModalDragStartAllowed";

describe("isModalDragStartAllowed", () => {
    it("returns false for null targets", () => {
        // Arrange
        const target = null;

        // Act
        const result = isModalDragStartAllowed(target);

        // Assert
        expect(result).toBe(false);
    });

    it("allows non-interactive header text targets", () => {
        // Arrange
        const { getByText } = render(
            <div data-slot="modal-title">
                Edit agent status
            </div>,
        );
        const title = getByText("Edit agent status");

        // Act
        const allowsElementTarget = isModalDragStartAllowed(title);
        const allowsTextNodeTarget = isModalDragStartAllowed(title.firstChild);

        // Assert
        expect(allowsElementTarget).toBe(true);
        expect(allowsTextNodeTarget).toBe(true);
    });

    it("blocks native interactive elements and descendants", () => {
        // Arrange
        const { container: buttonContainer } = render(
            <button>
                <span>Icon</span>
            </button>,
        );
        const button = buttonContainer.querySelector("button");
        const icon = buttonContainer.querySelector("span");

        const { container: linkContainer } = render(
            <a href="#">Open</a>,
        );
        const link = linkContainer.querySelector("a");

        const { container: inputContainer } = render(
            <input type="text" />,
        );
        const input = inputContainer.querySelector("input");

        if (button === null || icon === null || link === null || input === null) {
            throw new Error("Test setup failed to render interactive elements.");
        }

        // Act
        const buttonResult = isModalDragStartAllowed(button);
        const iconResult = isModalDragStartAllowed(icon);
        const linkResult = isModalDragStartAllowed(link);
        const inputResult = isModalDragStartAllowed(input);

        // Assert
        expect(buttonResult).toBe(false);
        expect(iconResult).toBe(false);
        expect(linkResult).toBe(false);
        expect(inputResult).toBe(false);
    });

    it("blocks semantic controls and explicit opt-out regions", () => {
        // Arrange
        const { container } = render(
            <div>
                <div role="button" data-testid="role-button" />
                <div data-modal-no-drag="" data-testid="no-drag" />
                <div data-slot="modal-handle-row" data-testid="handle-row" />
            </div>,
        );
        const roleButton = container.querySelector("[data-testid='role-button']");
        const noDrag = container.querySelector("[data-testid='no-drag']");
        const handleRow = container.querySelector("[data-testid='handle-row']");

        if (roleButton === null || noDrag === null || handleRow === null) {
            throw new Error("Test setup failed to render modal drag exclusion fixtures.");
        }

        // Act
        const roleButtonResult = isModalDragStartAllowed(roleButton);
        const noDragResult = isModalDragStartAllowed(noDrag);
        const handleRowResult = isModalDragStartAllowed(handleRow);

        // Assert
        expect(roleButtonResult).toBe(false);
        expect(noDragResult).toBe(false);
        expect(handleRowResult).toBe(false);
    });

    it("blocks contenteditable text regions", () => {
        // Arrange
        const { container } = render(
            <div contentEditable suppressContentEditableWarning>
                Editable
            </div>,
        );
        const editable = container.querySelector("[contenteditable]");

        if (editable === null) {
            throw new Error("Test setup failed to render contenteditable fixture.");
        }

        // Act
        const result = isModalDragStartAllowed(editable);

        // Assert
        expect(result).toBe(false);
    });
});
