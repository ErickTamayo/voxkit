import "../css/app.css";
import { ApolloProvider } from "@apollo/client/react";
import { StrictMode, Suspense, lazy, type FC } from "react";
import { createRoot } from "react-dom/client";
import { Redirect, Route, Switch } from "wouter";
import { AuthenticatedRoute } from "@/components/AuthenticatedRoute";
import { RouteWithLayout } from "@/components/RouteWithLayout";
import { Toaster } from "@/components/ui/toaster";
import RootLayout from "@/layouts/RootLayout";
import { apolloClient } from "@/lib/apolloClient";
import "@/i18n/config";
import { AuthenticationCardLayout } from "@/routes/authentication/layouts/AuthenticationCard.layout";

const HomeRoute = lazy(() => import("@/routes/home/home.route"));
const SignInRoute = lazy(() => import("@/routes/authentication/SignIn.route"));
const VerifyCodeRoute = lazy(
    () => import("@/routes/authentication/VerifyCode.route"),
);
const AccountRoute = lazy(
    () => import("@/routes/authentication/Authenticated.route"),
);

const AppContent: FC = () => {
    return (
        <ApolloProvider client={apolloClient}>
            <Suspense fallback={<p className="px-4 py-6 text-sm text-muted-foreground">Loading...</p>}>
                <Switch>
                    <RouteWithLayout path="/" component={HomeRoute} />
                    <RouteWithLayout
                        path="/signin"
                        component={SignInRoute}
                        layouts={[AuthenticationCardLayout]}
                    />
                    <RouteWithLayout
                        path="/verify/:email"
                        component={VerifyCodeRoute}
                        layouts={[AuthenticationCardLayout]}
                    />
                    <AuthenticatedRoute
                        path="/account"
                        component={AccountRoute}
                        layouts={[AuthenticationCardLayout]}
                    />
                    <Route>
                        <Redirect to="/" />
                    </Route>
                </Switch>
            </Suspense>
        </ApolloProvider>
    );
};

const App: FC = () => {
    return (
        <RootLayout>
            <AppContent />
            <Toaster />
        </RootLayout>
    );
};

const appElement = document.getElementById("app");

if (appElement !== null) {
    createRoot(appElement).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
