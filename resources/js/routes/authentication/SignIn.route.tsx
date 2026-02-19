import { useState, type FC, type FormEvent } from "react";
import { Redirect, useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useSession } from "@/hooks/useSession";

const SignInRoute: FC = () => {
    const [email, setEmail] = useState("test@example.com");
    const [requestErrorMessage, setRequestErrorMessage] = useState<string | null>(null);
    const [, setLocation] = useLocation();
    const {
        isRequestingAuthenticationCode,
        requestAuthenticationCode,
        status,
    } = useSession();

    async function handleSubmit(
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> {
        event.preventDefault();
        setRequestErrorMessage(null);

        try {
            const normalizedEmail = email.trim().toLowerCase();
            const result = await requestAuthenticationCode(normalizedEmail);
            if (result.ok) {
                setLocation(`/verify/${encodeURIComponent(normalizedEmail)}`);

                return;
            }

            setRequestErrorMessage(result.errorMessage ?? "Failed to send code.");
        } catch (error) {
            setRequestErrorMessage(error instanceof Error ? error.message : "Failed to send code.");
        }
    }

    if (status === "authenticated") {
        return <Redirect to="/account" />;
    }

    return (
        <form className="space-y-4" onSubmit={(event) => void handleSubmit(event)}>
            {requestErrorMessage !== null ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {requestErrorMessage}
                </div>
            ) : null}

            <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    value={email}
                    autoComplete="email"
                    required
                    onChange={(event) => setEmail(event.target.value)}
                    placeholder="you@example.com"
                />
            </div>

            <Button type="submit" disabled={isRequestingAuthenticationCode}>
                {isRequestingAuthenticationCode ? "Sending code..." : "Send code"}
            </Button>
        </form>
    );
};

export default SignInRoute;
