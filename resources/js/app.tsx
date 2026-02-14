import "../css/app.css";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { Button } from "@/components/ui/button";

function App() {
    return (
        <main className="grid min-h-screen place-items-center bg-muted p-6">
            <section className="w-full max-w-2xl rounded-xl border bg-card p-8 text-card-foreground shadow-xs">
                <p className="mb-4 inline-flex rounded-full bg-secondary px-3 py-1 text-xs font-semibold tracking-[0.06em] text-secondary-foreground uppercase">
                    Laravel + Vite + React + Capacitor + shadcn/ui
                </p>
                <h1 className="text-balance text-4xl leading-tight font-semibold sm:text-5xl">
                    Figma-themed shadcn setup is mounted
                </h1>
                <p className="mt-4 text-base leading-relaxed text-muted-foreground">
                    This app is rendered from <code>/resources/js/app.tsx</code>{" "}
                    and loaded by the Laravel <code>/</code> route.
                </p>
                <div className="mt-8 flex flex-wrap gap-3">
                    <Button>Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="outline">Outline</Button>
                    <Button variant="destructive">Destructive</Button>
                </div>
            </section>
        </main>
    );
}

const appElement = document.getElementById("app");

if (appElement !== null) {
    createRoot(appElement).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
