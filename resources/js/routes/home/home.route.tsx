import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useCurrentUser } from "@/routes/authentication/hooks/useCurrentUser";

export default function HomeRoute(): React.JSX.Element {
    const [, setLocation] = useLocation();
    const { user, isCheckingSession } = useCurrentUser();

    if (isCheckingSession) {
        return (
            <main className="grid min-h-screen place-items-center bg-muted p-6">
                <p className="text-sm text-muted-foreground">Checking your session...</p>
            </main>
        );
    }

    return (
        <main className="grid min-h-screen place-items-center bg-muted p-6">
            <section className="w-full max-w-xl space-y-6 rounded-xl border bg-card p-8 text-card-foreground shadow-xs">
                <div className="space-y-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Laravel + GraphQL + React
                    </p>
                    <h1 className="text-3xl font-semibold text-balance">Welcome</h1>
                    <p className="text-sm text-muted-foreground">
                        This is the app home. Use passwordless email code authentication to continue.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    {user === null ? (
                        <Button type="button" onClick={() => setLocation("/signin")}>
                            Sign in
                        </Button>
                    ) : (
                        <Button type="button" onClick={() => setLocation("/account")}>
                            Go to account
                        </Button>
                    )}
                </div>
            </section>
        </main>
    );
}
