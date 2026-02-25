import type { FC, PropsWithChildren } from "react";

interface RootLayoutProps extends PropsWithChildren {}

const RootLayout: FC<RootLayoutProps> = ({ children }) => {
    return <div className="app-root-viewport">{children}</div>;
};

export default RootLayout;
