import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";
import { useUser } from "@/hooks/useUser";

export default function AuthenticatedRoute(): React.JSX.Element {
    const { user } = useUser();
    const { isLoggingOut, logout } = useSession();

    async function handleLogout(): Promise<void> {
        await logout();
    }

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
}
