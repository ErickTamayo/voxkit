import { useMutation } from "@apollo/client/react";
import { type FormEvent, useState } from "react";
import { Redirect, useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { RequestAuthenticationCodeDocument } from "@/routes/authentication/authentication.graphql.ts";
import { useCurrentUser } from "@/routes/authentication/hooks/useCurrentUser";

export default function SignInRoute(): React.JSX.Element {
    const [email, setEmail] = useState("test@example.com");
    const [requestErrorMessage, setRequestErrorMessage] = useState<string | null>(null);
    const [, setLocation] = useLocation();
    const { user, isCheckingSession } = useCurrentUser();
    const [requestCode, { loading: isRequestingCode }] = useMutation(RequestAuthenticationCodeDocument);

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setRequestErrorMessage(null);

        try {
            const normalizedEmail = email.trim().toLowerCase();
            const result = await requestCode({
                variables: {
                    input: { email: normalizedEmail },
                },
            });

            const response = result.data?.requestAuthenticationCode;
            if (!response?.ok) {
                setRequestErrorMessage(response?.message ?? "Failed to send code.");

                return;
            }

            setLocation(`/verify/${encodeURIComponent(normalizedEmail)}`);
        } catch (error) {
            setRequestErrorMessage(error instanceof Error ? error.message : "Failed to send code.");
        }
    }

    if (isCheckingSession) {
        return <p className="text-sm text-muted-foreground">Checking your session...</p>;
    }

    if (user !== null) {
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

            <Button type="submit" disabled={isRequestingCode}>
                {isRequestingCode ? "Sending code..." : "Send code"}
            </Button>
        </form>
    );
}
