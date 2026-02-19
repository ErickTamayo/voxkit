import type { FC } from "react";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";

const HomeRoute: FC = () => {
    const [, setLocation] = useLocation();
    const { status } = useSession();

    return (
        <main className="grid min-h-screen place-items-center bg-muted p-6">
            <section className="w-full max-w-xl space-y-6 rounded-xl border bg-card p-8 text-card-foreground shadow-xs">
                <div className="space-y-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Laravel + GraphQL + React
                    </p>
                    <h1 className="text-3xl font-semibold text-balance">
                        Welcome
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        This is the app home. Use passwordless email code
                        authentication to continue.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    {status === "authenticated" ? (
                        <Button
                            type="button"
                            onClick={() => setLocation("/account")}
                        >
                            Go to account
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            onClick={() => setLocation("/signin")}
                        >
                            Sign in
                        </Button>
                    )}
                </div>
            </section>
        </main>
    );
};

export default HomeRoute;
