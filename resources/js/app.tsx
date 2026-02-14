import "../css/app.css";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";

function App() {
    return (
        <main className="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top_right,_#ffe7c8_0%,_#fff6ea_38%,_#f7f7f3_100%)] p-6 text-stone-950">
            <section className="w-full max-w-2xl rounded-2xl border border-amber-100 bg-amber-50/70 p-8 shadow-[0_18px_35px_-28px_rgba(82,57,20,0.45)] backdrop-blur-sm">
                <p className="mb-4 inline-flex rounded-full bg-amber-300 px-3 py-1 text-xs font-semibold tracking-[0.06em] text-amber-950 uppercase">
                    Laravel + Vite + React + Capacitor
                </p>
                <h1 className="text-balance text-4xl leading-tight font-semibold sm:text-5xl">
                    React SPA is mounted
                </h1>
                <p className="mt-4 text-base leading-relaxed text-stone-700">
                    This app is rendered from <code>/resources/js/app.tsx</code>{" "}
                    and loaded by the Laravel <code>/</code> route.
                </p>
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
