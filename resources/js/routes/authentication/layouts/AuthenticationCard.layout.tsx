import type { ReactNode } from "react";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";

type AuthenticationCardLayoutProps = {
    children: ReactNode;
};

export function AuthenticationCardLayout({ children }: AuthenticationCardLayoutProps): React.JSX.Element {
    return (
        <main className="grid min-h-screen place-items-center bg-muted p-6">
            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle>Passwordless Login</CardTitle>
                    <CardDescription>Sign in with a 6-digit code sent to your email.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">{children}</CardContent>
                <CardFooter>
                    <p className="text-xs text-muted-foreground">
                        Local dev shortcut: use <code>test@example.com</code> and code <code>123456</code>.
                    </p>
                </CardFooter>
            </Card>
        </main>
    );
}
