import "../css/app.css";
import { ApolloProvider } from "@apollo/client/react";
import { StrictMode, Suspense, lazy } from "react";
import { createRoot } from "react-dom/client";
import { Redirect, Route, Switch } from "wouter";
import { AuthenticatedRoute } from "@/components/AuthenticatedRoute";
import { RouteWithLayout } from "@/components/RouteWithLayout";
import { apolloClient } from "@/lib/apolloClient";
import { AuthenticationCardLayout } from "@/routes/authentication/layouts/AuthenticationCard.layout";

const HomeRoute = lazy(() => import("@/routes/home/home.route"));
const SignInRoute = lazy(() => import("@/routes/authentication/SignIn.route"));
const VerifyCodeRoute = lazy(
    () => import("@/routes/authentication/VerifyCode.route"),
);
const AccountRoute = lazy(
    () => import("@/routes/authentication/Authenticated.route"),
);

function App(): React.JSX.Element {
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
}

const appElement = document.getElementById("app");

if (appElement !== null) {
    createRoot(appElement).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
