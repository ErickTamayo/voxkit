import { Suspense, type FC } from "react";
import { ErrorBoundary, type FallbackProps } from "react-error-boundary";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";
import { useUser } from "@/hooks/useUser";

const AuthenticatedRouteContent: FC = () => {
    const { user } = useUser();
    const { isLoggingOut, logout } = useSession();

    const handleLogout = async (): Promise<void> => {
        await logout();
    };

    return (
        <div className="space-y-3">
            <div>
                <p className="text-sm text-muted-foreground">Signed in as</p>
                <p className="font-semibold">{user.name}</p>
                <p className="text-sm text-muted-foreground">{user.email}</p>
            </div>
            <Button
                type="button"
                variant="outline"
                onClick={() => void handleLogout()}
                disabled={isLoggingOut}
            >
                {isLoggingOut ? "Signing out..." : "Sign out"}
            </Button>
        </div>
    );
};

const AuthenticatedRouteLoading: FC = () => {
    return <p className="text-sm text-muted-foreground">Loading account details...</p>;
};

const AuthenticatedRouteErrorBoundary: FC<FallbackProps> = ({
    resetErrorBoundary,
}) => {
    const [, setLocation] = useLocation();

    return (
        <div className="space-y-3 rounded-md border border-destructive/30 bg-destructive/10 p-4">
            <p className="text-sm text-destructive">Could not load account details.</p>
            <div className="flex flex-wrap gap-2">
                <Button type="button" onClick={() => resetErrorBoundary()}>
                    Try again
                </Button>
                <Button type="button" variant="outline" onClick={() => setLocation("/")}>
                    Go home
                </Button>
            </div>
        </div>
    );
};

const AuthenticatedRoute: FC = () => {
    return (
        <ErrorBoundary FallbackComponent={AuthenticatedRouteErrorBoundary}>
            <Suspense fallback={<AuthenticatedRouteLoading />}>
                <AuthenticatedRouteContent />
            </Suspense>
        </ErrorBoundary>
    );
};

export default AuthenticatedRoute;
