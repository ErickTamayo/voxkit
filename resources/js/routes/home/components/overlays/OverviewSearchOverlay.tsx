import { useState, type FC } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Modal } from "@/components/ui/modal";

interface OverviewSearchOverlayProps {
    onOpenChange: (open: boolean) => void;
    open: boolean;
}

const OverviewSearchOverlay: FC<OverviewSearchOverlayProps> = ({
    onOpenChange,
    open,
}) => {
    const [query, setQuery] = useState<string>("");

    return (
        <Modal.Root open={open} onOpenChange={onOpenChange}>
            <Modal.Overlay />
            <Modal.Positioner>
                <Modal.Content className="md:w-[min(100%-2rem,38rem)]">
                    <Modal.Handle />

                    <Modal.Header>
                        <Modal.HeaderLeft>
                            <Modal.CloseButton />
                        </Modal.HeaderLeft>
                        <Modal.HeaderCenter>
                            <Modal.Title>Search</Modal.Title>
                            <Modal.Description>
                                Search surface scaffold for the Capacitor overview
                                flow.
                            </Modal.Description>
                        </Modal.HeaderCenter>
                        <Modal.HeaderRight />
                    </Modal.Header>

                    <Modal.Body>
                        <Modal.SafeAreaContent className="space-y-4">
                            <div className="relative">
                                <Search className="text-muted-foreground pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                <Input
                                    value={query}
                                    onChange={(event) => {
                                        setQuery(event.currentTarget.value);
                                    }}
                                    placeholder="Search jobs, auditions, invoices..."
                                    className="pl-9"
                                />
                            </div>

                            <section className="space-y-2">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Quick Filters
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <span className="rounded-full border border-border px-3 py-1 text-xs">
                                        Jobs
                                    </span>
                                    <span className="rounded-full border border-border px-3 py-1 text-xs">
                                        Auditions
                                    </span>
                                    <span className="rounded-full border border-border px-3 py-1 text-xs">
                                        Invoices
                                    </span>
                                </div>
                            </section>
                        </Modal.SafeAreaContent>
                    </Modal.Body>
                </Modal.Content>
            </Modal.Positioner>
        </Modal.Root>
    );
};

export { OverviewSearchOverlay };
export type { OverviewSearchOverlayProps };
