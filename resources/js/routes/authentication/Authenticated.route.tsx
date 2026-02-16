import { useMutation } from "@apollo/client/react";
import { useState } from "react";
import { Redirect, useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { clearAuthToken, shouldUseTokenAuth } from "@/lib/authSession";
import { LogoutDocument } from "@/routes/authentication/authentication.graphql.ts";
import { useCurrentUser } from "@/routes/authentication/hooks/useCurrentUser";

export default function AuthenticatedRoute(): React.JSX.Element {
    const [, setLocation] = useLocation();
    const useTokenAuth = shouldUseTokenAuth();
    const { user, refetchSession } = useCurrentUser();
    const [logout, { loading: isLoggingOut }] = useMutation(LogoutDocument);
    const [logoutErrorMessage, setLogoutErrorMessage] = useState<string | null>(null);

    async function handleLogout(): Promise<void> {
        setLogoutErrorMessage(null);

        try {
            const result = await logout();
            const response = result.data?.logout;
            if (!response) {
                setLogoutErrorMessage("Failed to log out.");

                return;
            }

            if (useTokenAuth) {
                clearAuthToken();
            }

            await refetchSession();
            setLocation("/");
        } catch (error) {
            setLogoutErrorMessage(error instanceof Error ? error.message : "Failed to log out.");
        }
    }

    if (user === null) {
        return <Redirect to="/signin" />;
    }

    return (
        <div className="space-y-3">
            {logoutErrorMessage !== null ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {logoutErrorMessage}
                </div>
            ) : null}
            <div>
                <p className="text-sm text-muted-foreground">Signed in as</p>
                <p className="font-semibold">{user.name}</p>
                <p className="text-sm text-muted-foreground">{user.email}</p>
            </div>
            <Button type="button" variant="outline" onClick={() => void handleLogout()} disabled={isLoggingOut}>
                {isLoggingOut ? "Signing out..." : "Sign out"}
            </Button>
        </div>
    );
}
