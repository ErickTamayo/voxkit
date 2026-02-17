import { REGEXP_ONLY_DIGITS } from "input-otp";
import { useState } from "react";
import { Redirect, useLocation, useParams } from "wouter";
import { Button } from "@/components/ui/button";
import { InputOTP, InputOTPGroup, InputOTPSeparator, InputOTPSlot } from "@/components/ui/input-otp";
import { useSession } from "@/hooks/useSession";

export default function VerifyCodeRoute(): React.JSX.Element {
    const params = useParams<{ email: string }>();
    const emailForCode = params?.email ? decodeURIComponent(params.email) : null;
    const [code, setCode] = useState("");
    const [verifyErrorMessage, setVerifyErrorMessage] = useState<string | null>(null);
    const [, setLocation] = useLocation();
    const {
        authenticateWithCode,
        isAuthenticatingWithCode,
        isRequestingAuthenticationCode,
        requestAuthenticationCode,
        status,
    } = useSession();

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
            const result = await authenticateWithCode({
                email: emailForCode,
                code,
            });
            if (!result.ok) {
                setVerifyErrorMessage(result.errorMessage ?? "Invalid or expired code.");
                setCode("");

                return;
            }

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
            const result = await requestAuthenticationCode(emailForCode);
            if (result.ok) {
                setCode("");

                return;
            }

            setVerifyErrorMessage(result.errorMessage ?? "Failed to resend code.");
        } catch (error) {
            setVerifyErrorMessage(error instanceof Error ? error.message : "Failed to resend code.");
        }
    }

    if (emailForCode === null) {
        return <Redirect to="/signin" />;
    }

    if (status === "authenticated") {
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
                <Button type="button" onClick={() => void handleVerifyCode()} disabled={!hasCompleteCode || isAuthenticatingWithCode}>
                    {isAuthenticatingWithCode ? "Verifying..." : "Verify code"}
                </Button>
                <Button type="button" variant="secondary" onClick={() => void handleResendCode()} disabled={isRequestingAuthenticationCode}>
                    {isRequestingAuthenticationCode ? "Resending..." : "Resend code"}
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
