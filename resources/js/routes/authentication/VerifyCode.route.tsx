import { useApolloClient } from "@apollo/client/react";
import { useMutation } from "@apollo/client/react";
import { REGEXP_ONLY_DIGITS } from "input-otp";
import { useState } from "react";
import { Redirect, useLocation, useParams } from "wouter";
import { Button } from "@/components/ui/button";
import { AuthMode } from "@/graphql/types";
import { InputOTP, InputOTPGroup, InputOTPSeparator, InputOTPSlot } from "@/components/ui/input-otp";
import { MeDocument } from "@/graphql/root.graphql.ts";
import { shouldUseTokenAuth, writeAuthToken } from "@/lib/authSession";
import { ensureSessionCsrfCookie } from "@/lib/csrf";
import {
    AuthenticateWithCodeDocument,
    RequestAuthenticationCodeDocument,
} from "@/routes/authentication/authentication.graphql.ts";
import { useCurrentUser } from "@/routes/authentication/hooks/useCurrentUser";

export default function VerifyCodeRoute(): React.JSX.Element {
    const params = useParams<{ email: string }>();
    const emailForCode = params?.email ? decodeURIComponent(params.email) : null;
    const useTokenAuth = shouldUseTokenAuth();
    const [code, setCode] = useState("");
    const [verifyErrorMessage, setVerifyErrorMessage] = useState<string | null>(null);
    const [, setLocation] = useLocation();
    const apolloClient = useApolloClient();
    const { user, isCheckingSession } = useCurrentUser();
    const [requestCode, { loading: isRequestingCode }] = useMutation(RequestAuthenticationCodeDocument);
    const [authenticateWithCode, { loading: isVerifyingCode }] = useMutation(AuthenticateWithCodeDocument);

    const hasCompleteCode = code.length === 6;

    async function handleVerifyCode(): Promise<void> {
        if (emailForCode === null) {
            setLocation("/signin");

            return;
        }

        if (!hasCompleteCode) {
            setVerifyErrorMessage("Enter all 6 digits.");

            return;
        }

        setVerifyErrorMessage(null);

        try {
            await ensureSessionCsrfCookie();
            const result = await authenticateWithCode({
                variables: {
                    input: {
                        email: emailForCode,
                        code,
                        mode: useTokenAuth ? AuthMode.Token : AuthMode.Session,
                        ...(useTokenAuth ? { device_name: "capacitor_app" } : {}),
                    },
                },
            });

            const response = result.data?.authenticateWithCode;
            if (!response?.ok) {
                setVerifyErrorMessage(response?.message ?? "Invalid or expired code.");
                setCode("");

                return;
            }

            if (useTokenAuth) {
                const token = response.token;
                if (typeof token !== "string" || token.length === 0) {
                    setVerifyErrorMessage("Authentication token was not returned.");
                    setCode("");

                    return;
                }

                writeAuthToken(token);
            } else {
                await ensureSessionCsrfCookie({ forceRefresh: true });
            }

            await apolloClient.refetchQueries({
                include: [MeDocument],
            });
            setLocation("/account");
        } catch (error) {
            setVerifyErrorMessage(error instanceof Error ? error.message : "Invalid code.");
            setCode("");
        }
    }

    async function handleResendCode(): Promise<void> {
        if (emailForCode === null) {
            setLocation("/signin");

            return;
        }

        setVerifyErrorMessage(null);

        try {
            await ensureSessionCsrfCookie();
            const result = await requestCode({
                variables: {
                    input: { email: emailForCode },
                },
            });

            const response = result.data?.requestAuthenticationCode;
            if (!response?.ok) {
                setVerifyErrorMessage(response?.message ?? "Failed to resend code.");

                return;
            }

            setCode("");
        } catch (error) {
            setVerifyErrorMessage(error instanceof Error ? error.message : "Failed to resend code.");
        }
    }

    if (emailForCode === null) {
        return <Redirect to="/signin" />;
    }

    if (isCheckingSession) {
        return <p className="text-sm text-muted-foreground">Checking your session...</p>;
    }

    if (user !== null) {
        return <Redirect to="/account" />;
    }

    return (
        <div className="space-y-4">
            {verifyErrorMessage !== null ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {verifyErrorMessage}
                </div>
            ) : null}

            <p className="text-sm text-muted-foreground">Enter the code sent to {emailForCode}.</p>

            <div className="flex justify-center">
                <InputOTP
                    maxLength={6}
                    pattern={REGEXP_ONLY_DIGITS}
                    value={code}
                    onChange={(value) => {
                        setCode(value);
                        setVerifyErrorMessage(null);
                    }}
                >
                    <InputOTPGroup>
                        <InputOTPSlot index={0} />
                        <InputOTPSlot index={1} />
                        <InputOTPSlot index={2} />
                    </InputOTPGroup>
                    <InputOTPSeparator />
                    <InputOTPGroup>
                        <InputOTPSlot index={3} />
                        <InputOTPSlot index={4} />
                        <InputOTPSlot index={5} />
                    </InputOTPGroup>
                </InputOTP>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button type="button" onClick={() => void handleVerifyCode()} disabled={!hasCompleteCode || isVerifyingCode}>
                    {isVerifyingCode ? "Verifying..." : "Verify code"}
                </Button>
                <Button type="button" variant="secondary" onClick={() => void handleResendCode()} disabled={isRequestingCode}>
                    {isRequestingCode ? "Resending..." : "Resend code"}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    onClick={() => {
                        setCode("");
                        setVerifyErrorMessage(null);
                        setLocation("/signin");
                    }}
                >
                    Use another email
                </Button>
            </div>
        </div>
    );
}
